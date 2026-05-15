<?php
namespace App\Application\Text;
use App\Entity\TextDocument;
use App\Infrastructure\TextParser\PlainTextParser;
use App\Infrastructure\TextParser\TextParserInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class UploadTextUseCase{
    public function __construct(private readonly PlainTextParser $plainTextParser, private readonly EntityManagerInterface $entityManager){}
    public function execute(string $title, string $sourceType, string $rawContent): TextDocument
    {
        $title = trim($title);

        if($title === ''){
            throw new InvalidArgumentException('Title cannot be empty');
        }
        if(!$this->plainTextParser->isValid($sourceType)){
            throw new InvalidArgumentException('Source type is invalid');
        }
        $content = $this->plainTextParser->parse($rawContent);
        if($content === ""){
            throw new InvalidArgumentException('Content cannot be empty');
        }
        $currentTime = new \DateTimeImmutable();

        $document = new TextDocument();
        $document->setTitle($title)
            ->setSourceType($sourceType)
            ->setContent($content)
            ->setLanguage('de')
            ->setCreatedAt($currentTime)
            ->setUpdatedAt($currentTime);
        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return $document;
    }

}

