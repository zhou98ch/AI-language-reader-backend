<?php

namespace App\Application\Text;

use App\Application\AI\Exception\DailyAiQuotaExceededException;
use App\Application\Text\DTO\ExplainWordResult;
use App\Application\Text\Exception\TextDocumentNotFoundException;
use App\Entity\WordExplanation;
use App\Infrastructure\AI\AiExplanationClientRegistry;
use App\Repository\TextDocumentRepository;
use App\Repository\WordExplanationRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;

final class ExplainWordUseCase
{
    private const INLINE_EXPLANATION = 'INLINE_EXPLANATION';
    private const CUSTOM_PROMPT = 'CUSTOM_PROMPT';

    public function __construct(
        private readonly TextDocumentRepository $textDocumentRepository,
        private readonly WordExplanationRepository $wordExplanationRepository,
        private readonly AiExplanationClientRegistry $aiClientRegistry,
        private readonly EntityManagerInterface $entityManager,
        private readonly int $geminiDailyCallLimit,
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return ExplainWordResult{
     *     word: string,
     *     context: string,
     *     explanation: string,
     *     cached: bool
     * }
     */
    public function execute(
        int $textDocumentId,
        string $word,
        string $context,
        string $provider = 'fake',
        ?string $prompt = null,
        ?int $startOffset = null,
        ?int $endOffset = null,
        string $explanationType = self::CUSTOM_PROMPT,
    ): ExplainWordResult {
        $document = $this->textDocumentRepository->find($textDocumentId);

        if (!$document) {
            throw new TextDocumentNotFoundException('text document not found');
        }

        $word = trim($word);
        $context = trim($context);
        $provider = strtolower(trim($provider));
        $prompt = $prompt !== null ? trim($prompt) : null;
        $prompt = $prompt === '' ? null : $prompt;
        $explanationType = strtoupper(trim($explanationType));

        if (!in_array($explanationType, [self::INLINE_EXPLANATION, self::CUSTOM_PROMPT], true)) {
            throw new InvalidArgumentException('invalid explanation type');
        }

        if ($explanationType === self::INLINE_EXPLANATION) {
            $prompt = $this->buildInlineExplanationPrompt();
        }

        $promptHash = hash('sha256', $prompt ?? '');

        if ($word === '') {
            throw new InvalidArgumentException('word are required');
        }
        if ($context === '') {
            throw new InvalidArgumentException('context are required');
        }
        if ($startOffset === null || $endOffset === null) {
            throw new InvalidArgumentException('startOffset and endOffset are required');
        }
        if ($startOffset < 0) {
            throw new InvalidArgumentException('startOffset must be greater than or equal to 0');
        }
        if ($endOffset <= $startOffset) {
            throw new InvalidArgumentException('endOffset must be greater than startOffset');
        }

        $existingExplanation = $this->wordExplanationRepository->findOneBy([
            'textDocument' => $document,
            'startOffset' => $startOffset,
            'endOffset' => $endOffset,
            'provider' => $provider,
            'promptHash' => $promptHash,
            'explanationType' => $explanationType,
        ]);

        if ($existingExplanation) {
            return new ExplainWordResult(
                word: $existingExplanation->getWord(),
                context: $existingExplanation->getContextText(),
                explanation: $existingExplanation->getExplanation(),
                cached: true,
                explanationType: $existingExplanation->getExplanationType() ?? $explanationType,
            );
        }

        $aiExplanationClient = $this->aiClientRegistry->get($provider);

        if ($provider === 'gemini') {
            $this->consumeGeminiDailyQuota();
        }

        $explanationData = $aiExplanationClient->explain($word, $context, prompt: $prompt);

        if ($explanationType === self::INLINE_EXPLANATION) {
            $explanationData = $this->normalizeInlineExplanation($explanationData);
        }

        $wordExplanation = new WordExplanation();
        $wordExplanation
            ->setTextDocument($document)
            ->setWord($word)
            ->setContextText($context)
            ->setStartOffset($startOffset)
            ->setEndOffset($endOffset)
            ->setProvider($provider)
            ->setPromptHash($promptHash)
            ->setExplanationType($explanationType)
            ->setExplanation($explanationData)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($wordExplanation);
        $this->entityManager->flush();

        return new ExplainWordResult(
            word: $wordExplanation->getWord(),
            context: $wordExplanation->getContextText(),
            explanation: $explanationData,
            cached: false,
            explanationType: $wordExplanation->getExplanationType() ?? $explanationType,
        );
    }

    private function buildInlineExplanationPrompt(): string
    {
        return <<<'PROMPT'
请根据上下文给德语单词生成原文内联短解释。

返回规则：
- 如果当前词形已经是基本形式，只返回中文释义
- 如果当前词形不是基本形式，返回“基本形式 中文释义”
- 动词基本形式用不定式
- 名词基本形式用单数主格
- 形容词基本形式用原级
- 如果是可分动词，基本形式返回完整不定式
- 中文释义最多 8 个汉字
- 整体最多 40 个字符
- 不要解释语法
- 不要加标点
- 不要返回 JSON
- 只返回一行
PROMPT;
    }

    private function normalizeInlineExplanation(string $explanation): string
    {
        $explanation = trim(preg_split('/\R/u', $explanation)[0] ?? '');
        $explanation = trim($explanation, " \t\n\r\0\x0B.,;:!?。；：！？");

        if ($explanation === '') {
            throw new RuntimeException('AI returned an empty inline explanation');
        }

        if (mb_strlen($explanation) > 40) {
            $explanation = mb_substr($explanation, 0, 40);
        }

        return $explanation;
    }

    private function consumeGeminiDailyQuota(): void
    {
        if ($this->geminiDailyCallLimit <= 0) {
            throw new DailyAiQuotaExceededException('gemini', $this->geminiDailyCallLimit);
        }

        $quotaFile = $this->projectDir . '/var/gemini_daily_quota.json';
        $quotaDir = dirname($quotaFile);

        if (!is_dir($quotaDir) && !mkdir($quotaDir, 0775, true) && !is_dir($quotaDir)) {
            throw new RuntimeException('Unable to create AI quota directory');
        }

        $handle = fopen($quotaFile, 'c+');

        if ($handle === false) {
            throw new RuntimeException('Unable to open AI quota file');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock AI quota file');
            }

            $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
            $contents = stream_get_contents($handle);
            $quota = is_string($contents) && $contents !== '' ? json_decode($contents, true) : [];

            if (!is_array($quota) || ($quota['date'] ?? null) !== $today) {
                $quota = [
                    'date' => $today,
                    'geminiCalls' => 0,
                ];
            }

            if (($quota['geminiCalls'] ?? 0) >= $this->geminiDailyCallLimit) {
                throw new DailyAiQuotaExceededException('gemini', $this->geminiDailyCallLimit);
            }

            ++$quota['geminiCalls'];

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($quota, JSON_PRETTY_PRINT));
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
