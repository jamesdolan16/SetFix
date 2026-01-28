<?php declare(strict_types=1);

namespace SetFix\Parser;

class Unary
{
    public function __construct(
        private Unary|Filter $child
    ){}

    public function getChild(): Unary|Filter
    {
        return $this->child;
    }

    public function toString(int $depth): string
    {
        return "\n" . str_repeat(' ', $depth * 2) . "(Unary" . $this->child->toString($depth + 1) . ")";
    }
}