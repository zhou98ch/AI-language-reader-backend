<?php

namespace App\Infrastructure\AI;

use Gemini;
use RuntimeException;

final class GeminiExplanationClient implements AiExplanationClientInterface
{
    public function __construct(
        private readonly string $myTempApiKey,
        private readonly string $myTempModel,
        //currently hardcode the api key (stored in .env.local) for dev environment
    ) {
    }

    public function getProvider(): string
    {
        return 'gemini';
    }

    public function explain(string $word, string $context, ?AiCredential $credential = null, ?string $prompt = null): string
    {
        $apiKey = $credential?->apiKey ?: $this->myTempApiKey;
        $model = $credential?->model ?: $this->myTempModel;

        if ($apiKey === '') {
            throw new RuntimeException('Gemini API key is not configured');
        }

        $prompt = ($prompt ?? "You explain German words for learners.Explain the word based on this context. Use clear language. You may include translation, grammar notes, and examples when useful.")
            . sprintf(
                " Word: %s, Context: %s. ",
                $word,
                $context
            );

        $client = Gemini::client($apiKey);

        $response = $client
            ->generativeModel($model)
            ->generateContent($prompt);

        $content = $response->text();

        return $content;
    }
}
