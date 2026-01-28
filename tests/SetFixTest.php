<?php declare(strict_types=1);

namespace SetFix\tests;

use PHPUnit\Framework\TestCase;
use SetFix\Evaluator\Evaluator;
use SetFix\Parser\Lexer;
use SetFix\Parser\Parser;

class SetFixTest extends TestCase
{
    public function testQuery(): void
    {
        $set = [
            ['id' => 'A', 'value' => 'Abra'],
            ['id' => 'B', 'value' => 'Boo'],
            ['id' => 'C', 'value' => 'Cadabra']
        ];

        $lexer = new Lexer();
        $parser = new Parser();
        $evaluator = new Evaluator($set, identifierKey: 'id', debug: true);
        $tokens = $lexer->tokenise('(*:value="Abra")A');
        $ast = $parser->parse($tokens);
        
        dump($evaluator->evaluate($ast));
    }
}