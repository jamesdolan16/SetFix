<?php declare(strict_types=1);

namespace SetFix\Moss\Parser\AstNodes;

final class Arithmetic implements Expression
{
    public string $operator;
    public Expression $left;
    public Expression $right;
}