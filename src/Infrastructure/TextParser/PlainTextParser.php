<?php
namespace App\Infrastructure\TextParser;
final class PlainTextParser implements TextParserInterface{
    /**
     * @param string $sourceType
     * @return bool
     * if type is txt then return true
     */
    public function isValid(string $sourceType): bool
    {
        return strtoupper($sourceType) === 'TXT';
    }

    /**
     * @param string $rawContent
     * @return string
     * TODO: currently on trim the spaces, maybe in future needs more complex logic
     */
    public function parse(string $rawContent): string
    {
        return trim($rawContent);
    }
}
