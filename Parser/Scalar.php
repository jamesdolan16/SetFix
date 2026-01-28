<?php declare(strict_types=1);

namespace SetFix\Parser;

class Scalar
{
    public function __construct(
        private string|int|float $value
    )
    {}

    public function getValue(): string|int|float
    {
        return $this->value;
    }

    public function getType(): string
    {
        return match(true) {
            is_string($this->value) => 'string',
            is_int($this->value) => 'int',
            is_float($this->value) => 'float'
        };
    }

    public function toString(int $depth): string
    {
        return "\n" . str_repeat(' ', $depth * 2) . "(Scalar {$this->value})";
    }
}