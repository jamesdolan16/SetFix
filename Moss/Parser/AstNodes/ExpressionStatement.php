<?php declare(strict_types=1);

namespace SetFix\Moss\Parser\AstNodes;

final class ExpressionStatement implements Statement
{
    public Expression $expression;
}