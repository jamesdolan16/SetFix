<?php declare(strict_types=1);

namespace SetFix\Moss\Parser\AstNodes;

final class Lambda implements Expression
{
    /** @var list<string> */
    public array $params;
    public Expression $body;
}