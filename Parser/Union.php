<?php declare(strict_types=1);

namespace SetFix\Parser;

class Union
{
    public function __construct(
        private array $intersections
    ){}

    public function getIntersections(): array
    {
        return $this->intersections;
    }

    public function addIntersection(Intersection $intersection): static
    {
        $this->intersections[] = $intersection;
        return $this;
    }

    public function toString(int $depth)
    {
        return "\n" . str_repeat(' ', $depth * 2) . "(Union" . implode("", 
            array_map(static fn($i) => $i->toString($depth + 1), $this->intersections)
        ) . ")";
    }
}