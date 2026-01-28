<?php declare(strict_types=1);

namespace SetFix;

use Closure;
use SetFix\Evaluator\Evaluator;
use SetFix\Parser\Lexer;
use SetFix\Parser\Parser;

/**
 * @see SetFix/README.md
 */
class SetFix
{
    private Lexer $lexer;
    private Parser $parser;
    private Evaluator $evaluator;

    private function __construct(
        private array &$set, 
        private Closure $indentifierCallback, 
        private bool $debug
    )
    {
        $this->lexer = new Lexer();
        $this->parser = new Parser();
        $this->evaluator = new Evaluator($set, $indentifierCallback, $debug);
    }

    public static function fromArray(array $set, Closure $indentifierCallback, bool $debug): SetFix
    {
        return new self($set, $indentifierCallback, $debug);
    }

    public function query(string $queryString): array
    {
        $tokens = $this->lexer->tokenise($queryString);
        $ast = $this->parser->parse($tokens);
        $result = $this->evaluator->evaluate($ast);

        return $result;
    }
}