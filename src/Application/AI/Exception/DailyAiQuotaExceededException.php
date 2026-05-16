<?php

namespace App\Application\AI\Exception;

use RuntimeException;

final class DailyAiQuotaExceededException extends RuntimeException
{
    public function __construct(
        private readonly string $provider,
        private readonly int $dailyCallLimit,
    ) {
        parent::__construct(sprintf(
            'Daily %s AI call limit reached. Cached explanations are still available.',
            $provider
        ));
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getDailyCallLimit(): int
    {
        return $this->dailyCallLimit;
    }
}
