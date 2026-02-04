<?php declare(strict_types=1);

namespace SetFix\Moss\Evaluator;

use SetFix\Moss\Parser\AstNodes\Arithmetic;
use SetFix\Moss\Parser\AstNodes\DefinitionStatement;
use SetFix\Moss\Parser\AstNodes\Expression;
use SetFix\Moss\Parser\AstNodes\ExpressionStatement;
use SetFix\Moss\Parser\AstNodes\Program;
use SetFix\Moss\Parser\AstNodes\Statement;
use SetFix\Moss\Parser\AstNodes\Identifier;

final class Evaluator
{
    private array $scope;
    private array $immediateScope;

    public function evaluateProgram(Program $program): string
    {
        $this->immediateScope =& $this->scope;
        return implode("\n", 
            array_map(fn(Statement $statement) => $this->evaluateStatement($statement), $program->statements)
        );
    }

    private function evaluateStatement(Statement $statement): ?string
    {
        return match(true) {
            $statement instanceof DefinitionStatement => $this->evaluateDefinitionStatement($statement),
            $statement instanceof ExpressionStatement => $this->evaluateExpressionStatement($statement)
        };
    }

    private function evaluateDefinitionStatement(DefinitionStatement $statement): void
    {
        $identifier = $statement->name;
        if ($this->identifierExistInImmediateScope($identifier)) throw new \LogicException("Cannot redefine '{$identifier}'");
        
        $binding = $this->immediateScope;
        foreach($identifier->parts as $part) {
            if ($part === $identifier->parts[array_key_last($identifier->parts)]) 
                $binding[$part] = $this->evaluateExpression($statement->value);
            else $binding = $binding[$part] ?? null;

            if (!$binding) throw new \LogicException("Could not resolve '$identifier'");
        }
    }
    
    private function evaluateExpressionStatement(ExpressionStatement $statement): void
    {
        $this->evaluateExpression($statement->expression);
    }

    private function evaluateExpression(Expression $expression): mixed
    {
        return match(true) {
            $expression instanceof Arithmetic => $this->evaluateArithmetic($expression)
        };
    }

    private function evaluateArithmetic(Arithmetic $arithmetic): int|float
    {
        
    }

    private function identifierExistInImmediateScope(Identifier $identifier): bool
    {
        $binding = $this->immediateScope;
        foreach($identifier->parts as $part) {
            $binding = $binding[$part] ?? null;
            if (!$binding) return false;
        }

        return true;
    }
}