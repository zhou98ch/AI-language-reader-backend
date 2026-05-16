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
    public function __construct(
        private readonly TextDocumentRepository $textDocumentRepository,
        private readonly WordExplanationRepository $wordExplanationRepository,
        private readonly AiExplanationClientRegistry $aiClientRegistry,
        private readonly EntityManagerInterface $entityManager,
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
        ?int $startOffset = null,
        ?int $endOffset = null,
    ): ExplainWordResult {
        $document = $this->textDocumentRepository->find($textDocumentId);

        if (!$document) {
            throw new TextDocumentNotFoundException('text document not found');
        }

        $word = trim($word);
        $context = trim($context);
        $provider = strtolower(trim($provider));

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
        ]);

        if ($existingExplanation) {
            return new ExplainWordResult(
                word: $existingExplanation->getWord(),
                context: $existingExplanation->getContextText(),
                explanation: $existingExplanation->getExplanation(),
                cached: true,
            );
        }
        $aiExplanationClient = $this->aiClientRegistry->get($provider);
        $explanationData = $aiExplanationClient->explain($word, $context);

        $wordExplanation = new WordExplanation();
        $wordExplanation
            ->setTextDocument($document)
            ->setWord($word)
            ->setContextText($context)
            ->setStartOffset($startOffset)
            ->setEndOffset($endOffset)
            ->setProvider($provider)
            ->setExplanation($explanationData)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($wordExplanation);
        $this->entityManager->flush();

        return new ExplainWordResult(
            word: $wordExplanation->getWord(),
            context: $wordExplanation->getContextText(),
            explanation: $explanationData,
            cached: false,
        );
    }
}
