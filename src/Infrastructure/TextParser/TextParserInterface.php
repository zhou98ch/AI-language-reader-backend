<?php

namespace App\Infrastructure\TextParser;

interface TextParserInterface
{

    /**
     * @param string $sourceType
     * @return bool
     * Returns true when this parser can handle the given source type.
     *
     * Examples: TXT, PDF, EPUB usw.
     */
    public function isValid(string $sourceType): bool;


    /**
     * @param string $rawContent
     * @return string
     * Convert the user-uploaded file into plain parsable text format
     */
    public function parse(string $rawContent): string;
}
