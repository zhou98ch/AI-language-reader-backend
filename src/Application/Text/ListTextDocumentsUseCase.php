<?php

namespace App\Application\Text;

use App\Repository\TextDocumentRepository;

final class ListTextDocumentsUseCase
{
    public function __construct(
        private readonly TextDocumentRepository $textDocumentRepository,
    ) {
    }

    public function execute(): array
    {
        $documents = $this->textDocumentRepository->findBy([], ['createdAt' => 'DESC']);
        $result = [];

        foreach ($documents as $document) {
            $result[] = [
                'id' => $document->getId(),
                'title' => $document->getTitle(),
                'sourceType' => $document->getSourceType(),
                'language' => $document->getLanguage(),
                'createdAt' => $document->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ];
        }

        return $result;
    }
}
