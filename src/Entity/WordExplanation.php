<?php

namespace App\Entity;

use App\Repository\WordExplanationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WordExplanationRepository::class)]
class WordExplanation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'wordExplanations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?TextDocument $textDocument = null;

    #[ORM\Column(length: 255)]
    private ?string $word = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contextText = null;

    #[ORM\Column(nullable: true)]
    private ?int $startOffset = null;

    #[ORM\Column(nullable: true)]
    private ?int $endOffset = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $provider = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $promptHash = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $explanation = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTextDocument(): ?TextDocument
    {
        return $this->textDocument;
    }

    public function setTextDocument(?TextDocument $textDocument): static
    {
        $this->textDocument = $textDocument;

        return $this;
    }

    public function getWord(): ?string
    {
        return $this->word;
    }

    public function setWord(string $word): static
    {
        $this->word = $word;

        return $this;
    }

    public function getContextText(): ?string
    {
        return $this->contextText;
    }

    public function setContextText(string $contextText): static
    {
        $this->contextText = $contextText;

        return $this;
    }

    public function getStartOffset(): ?int
    {
        return $this->startOffset;
    }

    public function setStartOffset(?int $startOffset): static
    {
        $this->startOffset = $startOffset;

        return $this;
    }

    public function getEndOffset(): ?int
    {
        return $this->endOffset;
    }

    public function setEndOffset(?int $endOffset): static
    {
        $this->endOffset = $endOffset;

        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getPromptHash(): ?string
    {
        return $this->promptHash;
    }

    public function setPromptHash(?string $promptHash): static
    {
        $this->promptHash = $promptHash;

        return $this;
    }

    public function getExplanation(): ?string
    {
        return $this->explanation;
    }

    public function setExplanation(string $explanation): static
    {
        $this->explanation = $explanation;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
