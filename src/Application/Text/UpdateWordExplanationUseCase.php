<?php

namespace App\Application\Text;

use App\Application\Text\Exception\TextDocumentNotFoundException;
use App\Application\Text\Exception\WordExplanationNotFoundException;
use App\Repository\TextDocumentRepository;
use App\Repository\WordExplanationRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final class UpdateWordExplanationUseCase
{
    public function __construct(
        private readonly TextDocumentRepository $textDocumentRepository,
        private readonly WordExplanationRepository $wordExplanationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function execute(int $textDocumentId, int $explanationId, string $explanationText): array
    {
        $document = $this->textDocumentRepository->find($textDocumentId);

        if (!$document) {
            throw new TextDocumentNotFoundException('text document not found');
        }

        $explanation = $this->wordExplanationRepository->find($explanationId);

        if (!$explanation || $explanation->getTextDocument()?->getId() !== $document->getId()) {
            throw new WordExplanationNotFoundException('word explanation not found');
        }

        $explanationText = trim($explanationText);

        if ($explanationText === '') {
            throw new InvalidArgumentException('explanation is required');
        }

        if ($explanation->getExplanationType() === 'INLINE_EXPLANATION' && mb_strlen($explanationText) > 40) {
            throw new InvalidArgumentException('inline explanation must be 40 characters or fewer');
        }

        $explanation->setExplanation($explanationText);
        $this->entityManager->flush();

        return [
            'id' => $explanation->getId(),
            'word' => $explanation->getWord(),
            'context' => $explanation->getContextText(),
            'startOffset' => $explanation->getStartOffset(),
            'endOffset' => $explanation->getEndOffset(),
            'provider' => $explanation->getProvider(),
            'promptHash' => $explanation->getPromptHash(),
            'explanationType' => $explanation->getExplanationType(),
            'explanation' => $explanation->getExplanation(),
            'createdAt' => $explanation->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
