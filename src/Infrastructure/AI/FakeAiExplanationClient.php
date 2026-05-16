<?php


namespace App\Infrastructure\AI;

final class FakeAiExplanationClient implements AiExplanationClientInterface
{
    public function getProvider(): string
    {
        return 'fake';
    }
    public function explain(string $word, string $context): array
    {
        return [
            'meaning' => 'temporary explanation for "' . $word . '"',
            'translation' => 'balabalal',
            'example' => $context,
            'grammarNote' => 'Real AI integration will be added later.',
        ];
    }
}
