<?php declare(strict_types=1);

namespace SetFix\Parser;

class Universe
{
    public function getType(): string
    {
        return self::class;
    }

    public function toString(int $depth)
    {
        return "\n" . str_repeat(' ', $depth * 2) . "(Universe)";
    }
}