<?php declare(strict_types=1);

namespace SetFix\Parser;

use App\Tests\Other\SetFixTest;

class Query
{
    public function __construct(
        private Universe|Grouping $set,
        private ?Union $union
    )
    {}

    public function getSet(): Universe|Grouping
    {
        return $this->set;
    }

    public function getUnion(): ?Union
    {
        return $this->union;
    }

    public function toString(int $depth)
    {
        return "\n" . str_repeat(' ', $depth * 2) . "(Query" . 
            $this->set->toString($depth + 1) .
            ($this->union?->toString($depth + 1) ?? '') . ")";
    }
}