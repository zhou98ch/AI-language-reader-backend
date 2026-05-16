<?php

namespace App\Application\Text\DTO;

final readonly class ExplainWordResult
{
    public function __construct(
        private string $word,
        private string $context,
        private string $explanation,
        private bool $cached,
        private string $explanationType,
    ) {
    }

    public function getWord(): string
    {
        return $this->word;
    }

    public function getContext(): string
    {
        return $this->context;
    }

    public function isCached(): bool
    {
        return $this->cached;
    }

    public function toArray(): array
    {
        return [
            'word' => $this->word,
            'context' => $this->context,
            'explanation' => $this->explanation,
            'cached' => $this->cached,
            'explanationType' => $this->explanationType,
        ];
    }

}
