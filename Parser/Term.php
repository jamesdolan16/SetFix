<?php declare(strict_types=1);

namespace SetFix\Parser;

class Term
{
    public function __construct(
        private Identifier|Scalar $child
    ){}

    public function getChild(): Identifier|Scalar
    {
        return $this->child;
    }

    public function toString(int $depth): string
    {
        return "\n" . str_repeat(' ', $depth * 2) . "(Term" . $this->child->toString($depth + 1);
    }
}