<?php declare(strict_types=1);

namespace SetFix\tests;

use PHPUnit\Framework\TestCase;
use SetFix\Parser\Lexer;
use SetFix\Parser\Token;

use function PHPUnit\Framework\assertEquals;

class LexerTest extends TestCase
{
    public function testBasicQuery(): void
    {
        $lexer = new Lexer();
        $tokens = $lexer->tokenise('*A|B|C');

        assertEquals([
            "TOKEN(UNIVERSE)",
            "TOKEN(ATOM 'A')",
            "TOKEN(UNION)",
            "TOKEN(ATOM 'B')",
            "TOKEN(UNION)",
            "TOKEN(ATOM 'C')"
        ], array_map(static fn(Token $token) => (string)$token, $tokens));
    }

    public function testComplexQuery(): void
    {
        $lexer = new Lexer();
        $tokens = $lexer->tokenise('(*:level<=10)');

        assertEquals([
            "TOKEN(L_PAREN)",
            "TOKEN(UNIVERSE)",
            "TOKEN(FILTER)",
            "TOKEN(ATOM 'level')",
            "TOKEN(COMPARISON '<=')",
            "TOKEN(INTEGER '10')",
            "TOKEN(R_PAREN)",
        ], array_map(static fn(Token $token) => (string)$token, $tokens));
    }

    public function testStringLiteral(): void
    {
        $lexer = new Lexer();
        $tokens = $lexer->tokenise('"This is a string literal"');

        assertEquals([
            "TOKEN(STRING 'This is a string literal')"
        ], array_map(static fn(Token $token) => (string)$token, $tokens));
    }

    public function testIntegerLiteral(): void
    {
        $lexer = new Lexer();
        $tokens = $lexer->tokenise('123');

        assertEquals([
            "TOKEN(INTEGER '123')"
        ], array_map(static fn(Token $token) => (string)$token, $tokens));
    }

    public function testFloatLiteral(): void
    {
        $lexer = new Lexer();
        $tokens = $lexer->tokenise('123.456');

        assertEquals([
            "TOKEN(DECIMAL '123.456')"
        ], array_map(static fn(Token $token) => (string)$token, $tokens));
    }
}