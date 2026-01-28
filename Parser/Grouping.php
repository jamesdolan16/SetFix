<?php declare(strict_types=1);

namespace SetFix\Parser;

class Grouping
{
    public function __construct(
        private Query $query
    )
    {}

    public function getQuery(): Query
    {
        return $this->query;
    }

    public function setQuery(Query $query): static
    {
        $this->query = $query;
        return $this;
    }

    public function toString(int $depth)
    {
        return "\n" . str_repeat(' ', $depth * 2) . "(Grouping" . $this->query->toString($depth + 1) . ")";
    }
}
