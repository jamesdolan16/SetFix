<?php declare(strict_types=1);

namespace SetFix\Parser;

final class Helpers
{
    /**
     * @return array{'head': mixed, 'tail': array}
     */
    public static function decap(array $a): array
    {
        return [$a[0] ?? null, array_slice($a, 1)];
    }
}