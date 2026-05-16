<?php


namespace App\Infrastructure\AI;

final class FakeAiExplanationClient implements AiExplanationClientInterface
{
    public function getProvider(): string
    {
        return 'fake';
    }
    public function explain(string $word, string $context): string
    {
        return sprintf(
            'Fake AI explanation for "%s" based on context: %s',
            $word,
            $context
        );
    }
}
