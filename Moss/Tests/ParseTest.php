<?php declare(strict_types=1);

namespace SetFix\Moss\Tests;

use PHPUnit\Framework\TestCase;
use SetFix\Moss\Parser\Lexer;
use SetFix\Moss\Parser\Parser;

class ParseTest extends TestCase
{
    public function testParse(): void
    {
        $lexer = new Lexer();
        $parser = new Parser();

        $tokens = $lexer->tokenise('d:=5*5; x:=1; j:=d*x;');
        echo($parser->stringifyTokenStream($tokens) . "\n");
        dump($parser->parse($tokens));
    }
}