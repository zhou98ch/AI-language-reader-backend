<?php

namespace App\Infrastructure\AI;

interface AiExplanationClientInterface
{
    /**
     * Returns the provider key used to select this AI client.
     *
     * Examples: fake, openai, gemini.
     */
    public function getProvider(): string;
    /**
     * Generates a context-based explanation for a word.
     */
    public function explain(string $word, string $context): string;
}
