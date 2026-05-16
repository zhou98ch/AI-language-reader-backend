<?php

namespace App\Infrastructure\AI;

use InvalidArgumentException;

final class AiExplanationClientRegistry
{
    /**
     * @param iterable<AiExplanationClientInterface> $clients
     */
    public function __construct(
        private readonly iterable $clients,
    ) {
    }

    public function get(string $provider): AiExplanationClientInterface
    {
        $provider = strtolower($provider);

        foreach ($this->clients as $client) {
            if ($client->getProvider() === $provider) {
                return $client;
            }
        }

        throw new InvalidArgumentException('unsupported AI provider');
    }
}
