<?php declare(strict_types=1);

namespace SetFix\Moss\Parser\AstNodes;

final class Concatenation implements Expression
{
    /**
     * @var list<Expression>
     */
    public array $expressions;
}