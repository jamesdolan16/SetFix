<?php declare(strict_types=1);

namespace SetFix\Moss\Parser\AstNodes;

final class ScalarLiteral implements Expression
{
    public int|float|string $value;
}