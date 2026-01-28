<?php declare(strict_types=1);

namespace SetFix\Parser;

class QuerySet
{
    public function __construct(
        private array $queries = []
    )
    {}

    public function getQuerySet(): array
    {
        return $this->queries;
    }

    public function addQuery(Query $query): static
    {
        $this->queries[] = $query;
        return $this;
    }

    public function toString(int $depth)
    {
        return str_repeat(' ', $depth * 2) . "(QuerySet" . implode("", 
            array_map(static fn($i) => $i->toString($depth + 1), $this->queries)
        ) . ")";
    }
}