<?php declare(strict_types=1);

namespace SetFix\Moss\Parser\AstNodes;

final class Identifier implements Expression
{
    /** @var list<string> */
    public array $parts;

    public function __toString()
    {
        return implode('.', $this->parts);
    }
}