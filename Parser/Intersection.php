<?php declare(strict_types=1);

namespace SetFix\Parser;

class Intersection
{
    public function __construct(
        private array $unaries
    ){}

    public function getUnaries(): array
    {
        return $this->unaries;
    }

    public function addUnary(Unary|Filter $unary): static
    {
        $this->unaries[] = $unary;
        return $this;
    }

    public function toString(int $depth)
    {
        return "\n" . str_repeat(' ', $depth * 2) . "(Intersection" . implode("", 
            array_map(static fn($u) => $u->toString($depth + 1), $this->unaries)
        ) . ")";
    }
}