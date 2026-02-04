<?php declare(strict_types=1);

namespace SetFix\Moss\Parser;

use SetFix\Moss\Parser\AstNodes\Arithmetic;
use SetFix\Moss\Parser\AstNodes\BinaryOp;
use SetFix\Moss\Parser\AstNodes\Call;
use SetFix\Moss\Parser\AstNodes\Comparison;
use SetFix\Moss\Parser\AstNodes\Concatenation;
use SetFix\Moss\Parser\AstNodes\Conditional;
use SetFix\Moss\Parser\AstNodes\Construction;
use SetFix\Moss\Parser\AstNodes\DefinitionStatement;
use SetFix\Moss\Parser\AstNodes\Expression;
use SetFix\Moss\Parser\AstNodes\ExpressionStatement;
use SetFix\Moss\Parser\AstNodes\Identifier;
use SetFix\Moss\Parser\AstNodes\Lambda;
use SetFix\Moss\Parser\AstNodes\Pipeline;
use SetFix\Moss\Parser\AstNodes\Program;
use SetFix\Moss\Parser\AstNodes\ReducerStage;
use SetFix\Moss\Parser\AstNodes\ScalarLiteral;
use SetFix\Moss\Parser\AstNodes\Statement;
use SetFix\Moss\Parser\AstNodes\Symbol;
use SetFix\Moss\Parser\AstNodes\Unary;

class Parser
{
    public array $tokens;
    public int $index;

    public function parse(array $tokens): Program
    {   
        
        $this->tokens = $tokens;
        $this->index = 0;
        return $this->parseProgram();
    }

    private function parseProgram(): Program
    {    
        $program = new Program();
        while($this->index < count($this->tokens)) {
            $statement = $this->tryStatement();
            if ($statement) $program->statements[] = $statement;
        }
        return $program;
    }

    private function tryStatement(): ?Statement
    {    

        if ($this->looksLikeDefinition()) $statement = $this->tryDefinitionStatement();
        else $statement = $this->expressionStatement();

        if ($statement && $this->match('semicolon')) {
            $this->consume('semicolon');
        }

        if (!$statement) throw new \LogicException('Expected DefinitionStatement or ExpressionStatement');
        return $statement;
    }

    private function looksLikeDefinition(): bool
    {   
        $i = $this->index + 1;
        if ($this->tokenAt($i)->kind === 'atom') {
            $i++;
        }
        return $this->tokenAt($i)->kind === 'assignment';
    }

    private function tryDefinitionStatement(): ?DefinitionStatement
    {   
        echo __FUNCTION__ . "\n";
        $name = $this->tryIdentifier();
        if (!$name) return null;
        
        if (!$this->match('assignment')) return null;

        $this->advance();

        $value = $this->expression();

        $node = new DefinitionStatement();
        $node->name = $name;
        $node->value = $value;

        return $node;
    }

    private function expressionStatement(): ExpressionStatement
    {    
        echo __FUNCTION__ . "\n";
        $expression = $this->expression();
        $statement = new ExpressionStatement();
        $statement->expression = $expression;
        return $statement;
    }

    private function tryIdentifier(): ?Identifier
    {    
        if (!$this->match('atom')) return null;
    
        $node = new Identifier();

        while($this->match('atom')) {
            $node->parts[] = $this->peek()->value;
            $this->advance();
            if (!$this->match('dot')) break;
            $this->advance();
        }

        return $node;
    }

    private function expression(): Expression
    {    
        static $depth = 0;
        if ($depth++ > 200) {
            throw new \RuntimeException("Parser recursion explosion at token {$this->index}");
        }
        $result = $this->conditional();
        $depth--;
        return $result;
    }

    private function conditional(): Expression
    {    
        if ($this->match('if')) {
            $node = new Conditional();

            $this->consume('if');
            $node->condition = $this->pipeline();
            $this->consume('then');
            $node->then = $this->pipeline();

            if ($this->match('else')) {
                $this->consume('else');
                $node->else = $this->pipeline();
            }

            return $node;
        }

        return $this->pipeline();
    }

    private function pipeline(): Expression
    {    
        $concatenation = $this->concatenation();
        if ($this->match('pipeline')) {
            $pipeline = new Pipeline();
            $pipeline->input = $concatenation;
            while($this->match('pipeline')) {
                $this->consume('pipeline');
                $pipeline->stages[] = $this->reducerStage();
            }
            return $pipeline;
        }

        return $concatenation;
    }

    private function concatenation(): Expression
    {
        $comparison = $this->comparison();
        if ($this->match('concat')) {
            $concatenation = new Concatenation();
            $concatenation->expressions[] = $comparison;
            while($this->match('concat')) {
                $this->consume('concat');
                $concatenation->expressions[] = $this->comparison();
            }
            return $concatenation;
        }

        return $comparison;
    }

    private function reducerStage(): ReducerStage
    {    
        $init = $this->expression();
        $reducer = $this->tryIdentifier();
        
        if (!$reducer) $reducer = $this->tryApplication();
        if (!$reducer) throw new \LogicException('Expected Lambda or Identifier as reducer');

        $reducerStage = new ReducerStage();
        $reducerStage->init = $init;
        $reducerStage->reducer = $reducer;

        return $reducerStage;
    }

    private function comparison(): Expression
    {    
        $left = $this->arithmetic();
        if($this->match('comparison')) {
            $operator = $this->consume('comparison')->value;
            $right = $this->arithmetic();
            $node = new Comparison();
            $node->operator = $operator;
            $node->left = $left;
            $node->right = $right;
            return $node;
        }

        return $left;
    }

    private function arithmetic(): Expression
    {
        $left = $this->unary();
        while($this->match('arithmetic')) {
            $operator = $this->consume('arithmetic')->value;
            $right = $this->unary();
            $node = new Arithmetic();
            $node->operator = $operator;
            $node->left = $left;
            $node->right = $right;
            $left = $node;  // Accumulate left
        }
        return $left;
    }

    private function unary(): Expression
    {    
        if ($this->match('unary') || ($this->match('arithmetic') && $this->peek()->value === '-')) {
            $unary = new Unary();
            $unary->operator = $this->peek()->value;
            $this->advance();
            $unary->value = $this->unary();
            return $unary;
        }

        return $this->primary();
    }

    private function primary(): Expression
    {    
        $expression = $this->tryApplication();
        $expression ??= $this->tryParenthesisedExpression();
        $expression ??= $this->tryConstruction();
        $expression ??= $this->tryIdentifier();
        $expression ??= $this->trySymbol();
        $expression ??= $this->tryScalar();
        if (!$expression) throw new \LogicException("Failed to parse rule 'primary'");

        return $expression;
    }

    private function tryApplication(): ?Expression
    {    
        if (!$this->match('l_brack')) return null;

        $this->consume('l_brack');

        $expression = $this->looksLikeLambda() ? $this->lambda() : $this->call();

        $this->consume('r_brack');
        return $expression;
        
        return null;
    }

    private function looksLikeLambda(): bool
    {    
        $i = $this->index + 1;
        while ($this->tokenAt($i)->kind === 'atom') {
            $i++;
        }
        return $this->tokenAt($i)->kind === 'arrow';
    }

    private function call(): Expression
    {    
        echo __FUNCTION__ . "\n";
        $call = new Call();
        $call->callee = $this->expression();

        while ($this->match('comma')) {
            $this->consume('comma');
            $call->args[] = $this->expression();
        }

        return $call;
    }

    private function lambda(): Expression
    {    
        $params = $this->lambdaParams();
        $this->consume('arrow');
        $body = $this->expression();

        $node = new Lambda();
        $node->params = $params;
        $node->body = $body;

        return $node;
    }

    private function tryLambda(): ?Expression
    {    
        try {
            $lambda = $this->lambda();
            return $lambda;
        } catch (\LogicException $e) {
            return null;
        }
    }

    /**
     * @return list<Identifier>
     */
    private function lambdaParams(): array
    {    
        $identifiers = [];
        while($this->match('atom')) {
            $identifier = $this->tryIdentifier();
            if (!$identifier) break;
            $identifiers[] = $identifier;
        }

        return $identifiers;
    }

    private function tryParenthesisedExpression(): ?Expression
    {    
        if (!$this->match('l_paren')) return null;
        $this->consume('l_paren');
        $expression = $this->expression();
        $this->consume('r_paren');

        return $expression;
    }

    private function tryConstruction(): ?Expression
    {    
        if (!$this->match('l_brace')) return null;
        
        $this->consume('l_brace');
        $construction = new Construction();
        while($this->index < count($this->tokens) && !$this->match('r_brace')) {
            $element = $this->tryConstructionElement();
            if ($element) $construction->elements[] = $element;
            if ($this->match('comma')) $this->consume('comma');
            else break;
        }
        dump($this->peek());
        $this->consume('r_brace');

        return $construction;
    }

    private function tryConstructionElement(): ?Expression
    {    
        // $expression = $this->tryConstruction();
        // $expression ??= $this->tryIdentifier();
        // $expression ??= $this->trySymbol();
        // $expression ??= $this->tryScalar();

        return $this->primary();
    }

    private function trySymbol(): ?Expression
    {    
        if (!$this->match('symbol')) return null;

        $node = new Symbol();
        $node->name = $this->consume('symbol')->value;

        return $node;
    }

    private function tryScalar(): ?Expression
    {    
        if (!in_array($this->peek()->kind, ['int_literal', 'float_literal', 'string_literal'])) return null;
        $scalar = new ScalarLiteral();
        $scalar->value = match($this->peek()->kind) {
            'int_literal' => (int)$this->peek()->value,
            'float_literal' => (float)$this->peek()->value,
            'string_literal' => (string)$this->peek()->value
        };
        $this->advance();

        return $scalar;
    }


    private function advance(): void
    {    
        $this->index++;
    }

    private function match(string $kind): bool
    {    
        $token = $this->peek();
        return $token !== null && $token->kind === $kind;
    }

    private function consume(string $kind): Token
    {    
        $currentToken = $this->peek();
        if ($this->match($kind)) {
            $currentToken = $this->peek();
            $this->advance();
            return $currentToken;
        }
        else {
            $remaining = $this->stringifyTokenStream(array_slice($this->tokens, $this->index));
            throw new \LogicException("Expected '$kind', found '{$currentToken->kind}', remaining '{$remaining}'");
        }
    }

    private function peek(): ?Token
    {
        return $this->index < count($this->tokens) ? $this->tokens[$this->index] : null;
    }

    private function tokenAt(int $index): ?Token
    {
        return $this->tokens[$index] ?? null;
    }

    public function stringifyTokenStream(array $tokenStream): string
    {
        return implode(" ", array_map(static fn ($e) => $e->kind, $tokenStream));
    }
}