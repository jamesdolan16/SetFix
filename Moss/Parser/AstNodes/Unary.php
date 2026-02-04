<?php declare(strict_types=1);

namespace SetFix\Moss\Parser\AstNodes;

final class Unary implements Expression
{
    public string $operator;
    public Expression $value;
}