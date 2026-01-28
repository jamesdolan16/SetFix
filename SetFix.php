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
        private ?string $identifierKey,
        private ?Closure $indentifierCallback, 
        private bool $debug
    )
    {
        $this->lexer = new Lexer();
        $this->parser = new Parser();
        $this->evaluator = new Evaluator($set, $identifierKey, $indentifierCallback, $debug);
    }

    public static function fromArray(
        array &$set,
        ?string $identifierKey = null,
        ?Closure $indentifierCallback = null, 
        bool $debug = false
    ): SetFix
    {
        if ($identifierKey && $indentifierCallback)
            throw new \LogicException("Ambigious identifier config, can't have both key and callback");

        return new self($set, $identifierKey, $indentifierCallback, $debug);
    }

    public function query(string $queryString): array
    {
        $tokens = $this->lexer->tokenise($queryString);
        $ast = $this->parser->parse($tokens);
        $result = $this->evaluator->evaluate($ast);

        return $result;
    }
}