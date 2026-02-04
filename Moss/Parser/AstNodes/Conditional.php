<?php declare(strict_types=1);

namespace SetFix\Moss\Parser\AstNodes;

final class Conditional implements Expression
{
    public Expression $condition;
    public Expression $then;
    public ?Expression $else;
}