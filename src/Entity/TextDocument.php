<?php

namespace App\Entity;

use App\Repository\TextDocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TextDocumentRepository::class)]
class TextDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(length: 50)]
    private ?string $sourceType = null;

    #[ORM\Column(length: 50)]
    private ?string $language = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, WordExplanation>
     */
    #[ORM\OneToMany(targetEntity: WordExplanation::class, mappedBy: 'textDocument', orphanRemoval: true)]
    private Collection $wordExplanations;

    public function __construct()
    {
        $this->wordExplanations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function setSourceType(string $sourceType): static
    {
        $this->sourceType = $sourceType;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, WordExplanation>
     */
    public function getWordExplanations(): Collection
    {
        return $this->wordExplanations;
    }

    public function addWordExplanation(WordExplanation $wordExplanation): static
    {
        if (!$this->wordExplanations->contains($wordExplanation)) {
            $this->wordExplanations->add($wordExplanation);
            $wordExplanation->setTextDocument($this);
        }

        return $this;
    }

    public function removeWordExplanation(WordExplanation $wordExplanation): static
    {
        if ($this->wordExplanations->removeElement($wordExplanation)) {
            if ($wordExplanation->getTextDocument() === $this) {
                $wordExplanation->setTextDocument(null);
            }
        }

        return $this;
    }
}
