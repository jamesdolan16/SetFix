<?php declare(strict_types=1);

namespace SetFix\Moss\Parser\AstNodes;

final class Pipeline implements Expression
{
    public Expression $input;
    /** @var list<ReducerStage> */
    public array $stages;
}