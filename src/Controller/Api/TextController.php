<?php

namespace App\Controller\Api;

use App\Application\Text\ExplainWordUseCase;
use App\Application\Text\Exception\TextDocumentNotFoundException;
use App\Application\Text\GetExplanationHistoryUseCase;
use App\Application\Text\GetTextDocumentUseCase;
use App\Application\Text\UploadTextUseCase;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class TextController extends AbstractController
{
    public function __construct(
        private readonly UploadTextUseCase $uploadTextUseCase,
        private readonly GetTextDocumentUseCase $getTextDocumentUseCase,
        private readonly ExplainWordUseCase $explainWordUseCase,
        private readonly GetExplanationHistoryUseCase $getExplanationHistoryUseCase,
    ) {
    }

    #[Route('/api/texts', name: 'api_texts_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);


        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $sourceType = trim($data['sourceType'] ?? '');
        try {

            $document = $this->uploadTextUseCase->execute($title, $sourceType, $content);

        } catch (InvalidArgumentException $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], 400);
        } catch (RuntimeException $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], 502);
        }

        return $this->json([
            'id' => $document->getId(),
            'title' => $document->getTitle(),
            'sourceType' => $document->getSourceType(),
            'createdAt' => $document->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ], 201);
    }

    #[Route('/api/texts/{id}', name: 'api_texts_getTextById', methods: ['GET'])]
    public function getTextById(int $id): JsonResponse
    {
        try {
            $document = $this->getTextDocumentUseCase->execute($id);
        } catch (TextDocumentNotFoundException $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], 404);
        }

        return $this->json([
            'id' => $document->getId(),
            'title' => $document->getTitle(),
            'content' => $document->getContent(),
            'sourceType' => $document->getSourceType(),
            'language' => $document->getLanguage(),
        ]);
    }

    #[Route('/api/texts/{docId}/explanations', name: 'api_texts_explain_word', methods: ['POST'])]
    public function explainWord(
        int $docId,
        Request $request,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $word = trim($data['word'] ?? '');
        $context = trim($data['context'] ?? '');
        $provider = trim($data['provider'] ?? 'fake');
        $prompt = trim($data['prompt'] ?? '');
        $startOffset = isset($data['startOffset']) ? (int) $data['startOffset'] : null;
        $endOffset = isset($data['endOffset']) ? (int) $data['endOffset'] : null;


        try {

            $result = $this->explainWordUseCase->execute(
                $docId,
                $word,
                $context,
                $provider,
                $prompt,
                $startOffset,
                $endOffset
            );

        } catch (TextDocumentNotFoundException $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], 404);
        } catch (InvalidArgumentException $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], 400);
        }




        return $this->json($result->toArray(), $result->isCached() ? 200 : 201); #200: OK, 201: created
    }

    #[Route('/api/texts/{id}/explanations', name: 'api_texts_get_explanation_history', methods: ['GET'])]
    public function getExplanationHistory(int $id): JsonResponse
    {
        try {
            $result = $this->getExplanationHistoryUseCase->execute($id);
        } catch (TextDocumentNotFoundException $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], 404);
        }

        return $this->json($result);
    }

}
