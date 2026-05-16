<?php

namespace App\Application\Text;

use App\Application\Text\Exception\TextDocumentNotFoundException;
use App\Repository\TextDocumentRepository;
use App\Repository\WordExplanationRepository;

final class GetExplanationHistoryUseCase
{
    public function __construct(
        private readonly TextDocumentRepository $textDocumentRepository,
        private readonly WordExplanationRepository $wordExplanationRepository,
    ) {
    }

    public function execute(int $textDocumentId): array
    {
        $document = $this->textDocumentRepository->find($textDocumentId);

        if (!$document) {
            throw new TextDocumentNotFoundException('text document not found');
        }

        $explanations = $this->wordExplanationRepository->findBy(
            ['textDocument' => $document],
            ['createdAt' => 'DESC']
        );

        $result = [];

        foreach ($explanations as $explanation) {
            $result[] = [
                'id' => $explanation->getId(),
                'word' => $explanation->getWord(),
                'context' => $explanation->getContextText(),
                'startOffset' => $explanation->getStartOffset(),
                'endOffset' => $explanation->getEndOffset(),
                'explanation' => json_decode($explanation->getExplanation(), true),
                'createdAt' => $explanation->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ];
        }

        return $result;
    }
}
