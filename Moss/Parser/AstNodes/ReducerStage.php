<?php declare(strict_types=1);

namespace SetFix\Moss\Parser\AstNodes;

final class ReducerStage
{
    public Expression $init;
    public Lambda|Identifier $reducer;
}

