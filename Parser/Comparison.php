<?php declare(strict_types=1);

namespace SetFix\Parser;

class Comparison
{
    public function __construct(
        private string $operation
    ){}

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function toString(int $depth)
    {
        return "\n" . str_repeat(' ', $depth * 2) . "(Comparison {$this->operation})";
    }
}