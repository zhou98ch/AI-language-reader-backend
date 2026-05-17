<?php

namespace App\Application\Text;

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
    ) {
    }

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
        return implode("\n", [
            'Generate a short inline explanation for a German word based on the context.',
            '',
            'Return rules:',
            '- If the current word form is already the base form, return only the English meaning.',
            '- If the current word form is not the base form, return "base form English meaning".',
            '- Use the infinitive as the base form for verbs.',
            '- Use singular nominative as the base form for nouns.',
            '- Use the positive form as the base form for adjectives.',
            '- If it is a separable verb, return the complete infinitive.',
            '- Keep the English meaning within 4 words.',
            '- Keep the whole answer within 40 characters.',
            '- Do not explain grammar.',
            '- Do not add punctuation.',
            '- Do not return JSON.',
            '- Return only one line.',
        ]);
    }

    private function normalizeInlineExplanation(string $explanation): string
    {
        // Sanitize AI output:
        // - keep only the first line
        // - remove extra spaces
        // - strip trailing punctuation
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
}
