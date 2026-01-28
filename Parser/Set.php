<?php declare(strict_types=1);

namespace SetFix\Parser;

class Set
{
    public function __construct(
        private Grouping $grouping
    ){}

    public function getType(): string
    {
        return self::class;
    }

    public function getGrouping(): Grouping
    {
        return $this->grouping;
    }

    public function setGrouping(Grouping $grouping): static
    {
        $this->grouping = $grouping;
        return $this;
    }

    public function toString(int $depth): string
    {
        return str_repeat(' ', $depth * 2) . "(Set\n" . $this->grouping->toString($depth + 1);
    }
}