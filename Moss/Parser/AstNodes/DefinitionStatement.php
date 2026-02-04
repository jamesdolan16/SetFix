<?php declare(strict_types=1);

namespace SetFix\Moss\Parser\AstNodes;

final class DefinitionStatement implements Statement
{
    public Identifier $name;
    public Expression $value;
}