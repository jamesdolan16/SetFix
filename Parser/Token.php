<?php declare(strict_types=1);

namespace SetFix\Parser;

class Token
{
    public function __construct(
        private readonly string $type,
        private readonly ?string $value = null
    ){}

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function __toString()
    {
        $valueStr = $this->value ? " '$this->value'" : '';
        return "TOKEN({$this->type}{$valueStr})";
    }
}