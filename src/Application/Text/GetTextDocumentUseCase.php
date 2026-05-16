<?php

namespace App\Application\Text;

use App\Application\Text\Exception\TextDocumentNotFoundException;
use App\Entity\TextDocument;
use App\Repository\TextDocumentRepository;

final class GetTextDocumentUseCase
{
    public function __construct(
        private readonly TextDocumentRepository $textDocumentRepository,
    ) {
    }

    public function execute(int $id): TextDocument
    {
        $document = $this->textDocumentRepository->find($id);

        if (!$document) {
            throw new TextDocumentNotFoundException('text document not found');
        }

        return $document;
    }
}
