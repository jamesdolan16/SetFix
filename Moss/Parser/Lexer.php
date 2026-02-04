<?php declare(strict_types=1);

namespace SetFix\Moss\Parser;

use RuntimeException;
use SetFix\Parser\Helpers as h;

class Lexer
{
    public function __construct(){}

    /**
     * @return list<Token>
     */
    public function tokenise(string $query): array
    {
        $chars = str_split($query);

        if(count($chars) === 0) throw new \InvalidArgumentException('Empty query');
        return $this->next($chars, []);
    }

    /**
     * @return list<Token>
     */
    private function next(array $chars, array $tokens, int $position = 0): array
    {
        if (count($chars) === 0) return $tokens;
        [$head, $tail] = h::decap($chars);
        $nextPosition = $position + 1;
        
        return match(true) {
            $head === '(' => $this->next($tail, [...$tokens, new Token('l_paren')], $nextPosition),
            $head === ')' => $this->next($tail, [...$tokens, new Token('r_paren')], $nextPosition),
            $head === '[' => $this->next($tail, [...$tokens, new Token('l_brack')], $nextPosition),
            $head === ']' => $this->next($tail, [...$tokens, new Token('r_brack')], $nextPosition),
            $head === '{' => $this->next($tail, [...$tokens, new Token('l_brace')], $nextPosition),
            $head === '}' => $this->next($tail, [...$tokens, new Token('r_brace')], $nextPosition),
            $head === '+' => $this->next($tail, [...$tokens, new Token('arithmetic', '+')], $nextPosition),
            $head === '*' => $this->next($tail, [...$tokens, new Token('arithmetic', '*')], $nextPosition),
            $head === '/' => $this->next($tail, [...$tokens, new Token('arithmetic', '/')], $nextPosition),
            $head === '|' => $this->next($tail, [...$tokens, new Token('concat')], $nextPosition),
            $head === ',' => $this->next($tail, [...$tokens, new Token('comma')], $nextPosition),
            $head === '=' => $this->next($tail, [...$tokens, new Token('comparison', '=')], $nextPosition),
            $head === '!' => $this->next($tail, [...$tokens, new Token('unary', '!')], $nextPosition),
            $head === ';' => $this->next($tail, [...$tokens, new Token('semicolon')], $nextPosition),
            $head === '-' => $this->dash($tail, $tokens, $position),
            $head === '~' => $this->tilde($tail, $tokens, $position),
            $head === ':' => $this->colon($tail, $tokens, $position),
            $head === '>' => $this->greater($tail, $tokens, $position),
            $head === '<' => $this->less($tail, $tokens, $position),
            $head === '"' => $this->stringLiteral([], $tail, $tokens, $position, '"'),
            $head === '\'' => $this->stringLiteral([], $tail, $tokens, $position, '\''),
            ctype_alpha($head) || $head === '_' => $this->atom([], $chars, $tokens, $position),
            ctype_digit($head) => $this->numericLiteral([], $chars, $tokens, $position),
            ctype_space($head) => $this->next($tail, $tokens, $nextPosition),      // Ignore whitespace
            default => throw new \RuntimeException("Unexpected character '{$head}' at position {$position}")
        };
    }

    private function dash(array $chars, array $tokens, int $position): array
    {
        [$head, $tail] = h::decap($chars);
        $nextPosition = $position + 1;

        return match ($head) {
            '>' => $this->next($tail, [...$tokens, new Token('arrow')], $nextPosition),
            default => $this->next($chars, [...$tokens, new Token('arithmetic', '-')], $nextPosition)
        };
    }
    
    private function tilde(array $chars, array $tokens, int $position): array
    {
        [$head, $tail] = h::decap($chars);
        $nextPosition = $position + 1;

        return match ($head) {
            '>' => $this->next($tail, [...$tokens, new Token('pipeline')], $nextPosition),
            default => throw new \LogicException("Unexpected character in input stream '~', did you mean '~>'?")
        };
    }

    private function colon(array $chars, array $tokens, int $position): array
    {
        [$head, $tail] = h::decap($chars);
        $nextPosition = $position + 1;

        return match (true) {
            $head === '=' => $this->next($tail, [...$tokens, new Token('assignment')], $nextPosition),
            $head === ':' => $this->next($tail, [...$tokens, new Token('signature')], $nextPosition),
            $this->validAtomChar($head) => $this->symbol([], $tail, $tokens, $head),
            default => $this->next($chars, [...$tokens, new Token('colon')], $nextPosition)
        };
    }

    private function symbol(array $symbol, array $chars, array $tokens, int $position): array
    {
        if (count($chars) === 0) return $this->next($chars, [...$tokens, new Token('symbol', implode($symbol))], $position);

        [$head, $tail] = h::decap($chars);
        $nextPosition = $position + 1;
        
        if ($this->validAtomChar($head)) { 
            return $this->symbol(
                [...$symbol, $head], 
                $tail, 
                $tokens, 
                $nextPosition
            );
        }
        
        return $this->next($chars, [...$tokens, new Token('symbol', implode($symbol))], $nextPosition);
    }

    private function less(array $chars, array $tokens, int $position): array
    {
        [$head, $tail] = h::decap($chars);
        $nextPosition = $position + 1;

        return match($head) {
            '=' => $this->next($tail, [...$tokens, new Token('comparison', '<=')], $nextPosition),
            default => $this->next($chars, [...$tokens, new Token('comparison', '<')], $nextPosition)
        };
    }

    private function greater(array $chars, array $tokens, int $position): array
    {
        [$head, $tail] = h::decap($chars);
        $nextPosition = $position + 1;

        return match($head) {
            '=' => $this->next($tail, [...$tokens, new Token('comparison', '>=')], $nextPosition),
            default => $this->next($chars, [...$tokens, new Token('comparison', '>')], $nextPosition)
        };
    }

    private function stringLiteral(array $literal, array $chars, array $tokens, int $position, string $terminator): array
    {
        if (count($chars) === 0) throw new \RuntimeException('Unterminated string literal in query');

        [$head, $tail] = h::decap($chars);
        $nextPosition = $position + 1;
        
        if ($head !== $terminator) { 
            return $this->stringLiteral(
                [...$literal, $head], 
                $tail, 
                $tokens, 
                $nextPosition,
                $terminator
            );
        }
        
        return $this->next($tail, [...$tokens, new Token('string_literal', implode($literal))], $nextPosition);
    }

    private function numericLiteral(array $literal, array $chars, array $tokens, int $position): array
    {
        if (count($chars) === 0) return $this->next($chars, [...$tokens, new Token('int_literal', implode($literal))], $position);

        [$head, $tail] = h::decap($chars);
        $nextPosition = $position + 1;
        
        if ($head === '.') { 
            return $this->consumeDecimal([...$literal, $head], $tail, $tokens, $nextPosition);
        }

        if (ctype_digit($head)) {
            return $this->numericLiteral(
                [...$literal, $head], 
                $tail, 
                $tokens, 
                $nextPosition
            );
            
        }
        
        return $this->next($chars, [...$tokens, new Token('int_literal', implode($literal))], $nextPosition);
    }

    private function consumeDecimal(array $literal, array $chars, array $tokens, int $position): array
    {
        if (count($chars) === 0) return $this->next($chars, [...$tokens, new Token('float_Literal', implode($literal))], $position); 

        [$head, $tail] = h::decap($chars);
        $nextPosition = $position + 1;
        
        if ($head === '.') { 
            throw new \RuntimeException("Invalid second '.' in decimal, position {$position}");
        }

        if (ctype_digit($head)) {
            return $this->consumeDecimal(
                [...$literal, $head], 
                $tail, 
                $tokens, 
                $nextPosition
            );
        }
        
        return $this->next($chars, [...$tokens, new Token('float_literal', implode($literal))], $nextPosition);    
    }

    /**
     * @return list<Token>
     */
    private function atom(array $atom, array $chars, array $tokens, int $position): array
    {
        if (count($chars) === 0) return $this->next($chars, [...$tokens, new Token('atom', implode($atom))], $position);

        [$head, $tail] = h::decap($chars);
        $nextPosition = $position + 1;
        
        if ($this->validAtomChar($head)) { 
            return $this->atom(
                [...$atom, $head], 
                $tail, 
                $tokens, 
                $nextPosition
            );
        }
        
        return $this->next($chars, [...$tokens, new Token('atom', implode($atom))], $nextPosition);
    }

    private function validAtomChar(string $char): bool
    {
        return ctype_alnum($char) || $char === '_' || $char === '-';
    }
}