<?php

namespace App\Controller\Api;

use App\Infrastructure\TextParser\TextParserInterface;
use App\Application\Text\UploadTextUseCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\TextDocument;
use App\Repository\TextDocumentRepository;
use App\Entity\WordExplanation;
use App\Repository\WordExplanationRepository;
use InvalidArgumentException;

final class TextController extends AbstractController
{
    public function __construct(private readonly UploadTextUseCase  $uploadTextUseCase){}
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
        }



        return $this->json([
            'id' => $document->getId(),
            'title' => $document->getTitle(),
            'sourceType' => $document->getSourceType(),
            'createdAt' => $document->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ], 201);
    }

    #[Route('/api/texts/{id}', name: 'api_texts_getTextById', methods: ['GET'])]
    public function getTextById(int $id, TextDocumentRepository $textDocumentRepository): JsonResponse
    {
        $document = $textDocumentRepository->find($id);

        if (!$document) {
            return $this->json([
                'error' => 'text document not found',
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

    #[Route('/api/texts/{id}/explanations', name: 'api_texts_explain_word', methods: ['POST'])]
    public function explainWord(
        int $id,
        Request $request,
        TextDocumentRepository $textDocumentRepository,
        WordExplanationRepository $wordExplanationRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $document = $textDocumentRepository->find($id);

        if (!$document) {
            return $this->json([
                'error' => 'text document not found',
            ], 404);
        }

        $data = json_decode($request->getContent(), true);

        $word = trim($data['word'] ?? '');
        $context = trim($data['context'] ?? '');

        if ($word === '' || $context === '') {
            return $this->json([
                'error' => 'word and context are required',
            ], 400);
        }

        $existingExplanation = $wordExplanationRepository->findOneBy([
            'textDocument' => $document,
            'word' => $word,
            'contextText' => $context,
        ]);

        if ($existingExplanation) {
            return $this->json([
                'word' => $existingExplanation->getWord(),
                'context' => $existingExplanation->getContextText(),
                'explanation' => json_decode($existingExplanation->getExplanation(), true),
            ]);
        }

        $explanationData = [
            'meaning' => 'temporary explanation',
            'translation' => 'xxxx',
            'example' => $context,
            'grammarNote' => 'AI integration will be added later',
        ];

        $wordExplanation = new WordExplanation();
        $wordExplanation
            ->setTextDocument($document)
            ->setWord($word)
            ->setContextText($context)
            ->setExplanation(json_encode($explanationData, JSON_UNESCAPED_UNICODE))
            ->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($wordExplanation);
        $entityManager->flush();

        return $this->json([
            'word' => $wordExplanation->getWord(),
            'context' => $wordExplanation->getContextText(),
            'explanation' => $explanationData,
        ], 201);
    }

    #[Route('/api/texts/{id}/explanations', name: 'api_texts_get_explanation_history', methods: ['GET'])]
    public function getExplanationHistory(
        int $id,
        TextDocumentRepository $textDocumentRepository,
        WordExplanationRepository $wordExplanationRepository,
    ): JsonResponse {
        $document = $textDocumentRepository->find($id);

        if (!$document) {
            return $this->json([
                'error' => 'text document not found',
            ], 404);
        }

        $explanations = $wordExplanationRepository->findBy(
            ['textDocument' => $document],
            ['createdAt' => 'DESC']
        );

        $result = [];

        foreach ($explanations as $explanation) {
            $result[] = [
                'id' => $explanation->getId(),
                'word' => $explanation->getWord(),
                'context' => $explanation->getContextText(),
                'createdAt' => $explanation->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            ];
        }

        return $this->json($result);
    }

}
