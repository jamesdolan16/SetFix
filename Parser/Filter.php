<?php declare(strict_types=1);

namespace SetFix\Parser;

class Filter
{
    public function __construct(
        private MetadataPredicate|Term $child
    ){}

    public function getChild(): MetadataPredicate|Term
    {
        return $this->child;
    }

    public function toString(int $depth)
    {
        return "\n" . str_repeat(' ', $depth * 2) . "(Filter" . $this->child->toString($depth + 1) . ")";
    }
}