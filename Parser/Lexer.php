<?php declare(strict_types=1);

namespace SetFix\Parser;

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
            $head === '*' => $this->next($tail, [...$tokens, new Token('UNIVERSE')], $nextPosition),
            $head === '(' => $this->next($tail, [...$tokens, new Token('L_PAREN')], $nextPosition),
            $head === ')' => $this->next($tail, [...$tokens, new Token('R_PAREN')], $nextPosition),
            $head === '|' => $this->next($tail, [...$tokens, new Token('UNION')], $nextPosition),
            $head === '&' => $this->next($tail, [...$tokens, new Token('INTERSECTION')], $nextPosition),
            $head === ':' => $this->next($tail, [...$tokens, new Token('FILTER')], $nextPosition),
            $head === ',' => $this->next($tail, [...$tokens, new Token('CONCAT')], $nextPosition),
            $head === '=' => $this->next($tail, [...$tokens, new Token('COMPARISON', '=')], $nextPosition),
            $head === '!' => $this->next($tail, [...$tokens, new Token('EXCLUSION')], $nextPosition),
            $head === '>' => $this->consumeGreater($tail, $tokens, $position),
            $head === '<' => $this->consumeLess($tail, $tokens, $position),
            $head === '"' => $this->consumeStringLiteral([], $tail, $tokens, $position, '"'),
            $head === '\'' => $this->consumeStringLiteral([], $tail, $tokens, $position, '\''),
            ctype_alpha($head) || $head === '_' => $this->consumeAtom([], $chars, $tokens, $position),
            ctype_digit($head) => $this->consumeNumericLiteral([], $chars, $tokens, $position),
            $head === ' ' => $this->next($tail, $tokens, $nextPosition),      // Ignore whitespace
            default => throw new \RuntimeException("Unexpected character '{$head}' at position {$position}")
        };
    }

    private function consumeLess(array $chars, array $tokens, int $position): array
    {
        [$head, $tail] = h::decap($chars);
        $nextPosition = $position + 1;

        return match($head) {
            '=' => $this->next($tail, [...$tokens, new Token('COMPARISON', '<=')], $nextPosition),
            default => $this->next($chars, [...$tokens, new Token('COMPARISON', '<')], $nextPosition)
        };
    }

    private function consumeGreater(array $chars, array $tokens, int $position): array
    {
        [$head, $tail] = h::decap($chars);
        $nextPosition = $position + 1;

        return match($head) {
            '=' => $this->next($tail, [...$tokens, new Token('COMPARISON', '>=')], $nextPosition),
            default => $this->next($chars, [...$tokens, new Token('COMPARISON', '>')], $nextPosition)
        };
    }

    private function consumeStringLiteral(array $literal, array $chars, array $tokens, int $position, string $terminator): array
    {
        if (count($chars) === 0) throw new \RuntimeException('Unterminated string literal in query');

        [$head, $tail] = h::decap($chars);
        $nextPosition = $position + 1;
        
        if ($head !== $terminator) { 
            return $this->consumeStringLiteral(
                [...$literal, $head], 
                $tail, 
                $tokens, 
                $nextPosition,
                $terminator
            );
        }
        
        return $this->next($tail, [...$tokens, new Token('STRING', implode($literal))], $nextPosition);
    }

    private function consumeNumericLiteral(array $literal, array $chars, array $tokens, int $position): array
    {
        if (count($chars) === 0) return $this->next($chars, [...$tokens, new Token('INTEGER', implode($literal))], $position);

        [$head, $tail] = h::decap($chars);
        $nextPosition = $position + 1;
        
        if ($head === '.') { 
            return $this->consumeDecimal([...$literal, $head], $tail, $tokens, $nextPosition);
        }

        if (ctype_digit($head)) {
            return $this->consumeNumericLiteral(
                [...$literal, $head], 
                $tail, 
                $tokens, 
                $nextPosition
            );
            
        }
        
        return $this->next($chars, [...$tokens, new Token('INTEGER', implode($literal))], $nextPosition);
    }

    private function consumeDecimal(array $literal, array $chars, array $tokens, int $position): array
    {
        if (count($chars) === 0) return $this->next($chars, [...$tokens, new Token('DECIMAL', implode($literal))], $position); 

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
        
        return $this->next($chars, [...$tokens, new Token('DECIMAL', implode($literal))], $nextPosition);    
    }

    /**
     * @return list<Token>
     */
    private function consumeAtom(array $atom, array $chars, array $tokens, int $position): array
    {
        if (count($chars) === 0) return $this->next($chars, [...$tokens, new Token('ATOM', implode($atom))], $position);

        [$head, $tail] = h::decap($chars);
        $nextPosition = $position + 1;
        
        if (ctype_alnum($head) || $head === '_' || $head === '-' || $head === '.') { 
            return $this->consumeAtom(
                [...$atom, $head], 
                $tail, 
                $tokens, 
                $nextPosition
            );
        }
        
        return $this->next($chars, [...$tokens, new Token('ATOM', implode($atom))], $nextPosition);
    }

}