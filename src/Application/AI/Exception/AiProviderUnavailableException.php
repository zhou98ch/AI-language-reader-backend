<?php

namespace App\Application\AI\Exception;

use RuntimeException;

final class AiProviderUnavailableException extends RuntimeException
{
    public function __construct(
        private readonly string $provider,
        private readonly int $statusCode = 502,
        ?string $message = null,
    ) {
        parent::__construct($message ?? sprintf('%s is temporarily unavailable', $provider));
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
