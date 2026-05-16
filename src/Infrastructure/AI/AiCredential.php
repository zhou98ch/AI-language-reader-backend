<?php

namespace App\Infrastructure\AI;

final readonly class AiCredential
{
    public function __construct(
        public string $apiKey,
        public ?string $model = null,
    ) {
    }
}
