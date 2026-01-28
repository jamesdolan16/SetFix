<?php declare(strict_types=1);

namespace SetFix\Parser;

class MetadataIdentifier
{
    public function __construct(
        private string $value
    ){}

    public function getValue(): string
    {
        return $this->value;
    }
    
    public function toString(int $depth)
    {
        return "\n" . str_repeat(' ', $depth * 2) . "(MetadataIdentifier {$this->value})";
    }
}