<?php declare(strict_types=1);

namespace SetFix\Parser;

use SetFix\Parser\Helpers as h;

class Parser
{
    public function __construct(){}

    /**
     * @param list<Token> $tokens
     */
    public function parse(array $tokens): QuerySet
    {
        return $this->consumeQuerySet($tokens);
    }

    private function consumeQuerySet(array $tokens): QuerySet
    {
        [$left, $rest] = $this->tryConsumeQuery($tokens);
        $nodes = [$left];

        while ($this->peek($rest)?->getType() === 'CONCAT') {
            [$head, $tail] = h::decap($rest);

            $result = $this->tryConsumeQuery($tail);
            if (!$result) throw new \RuntimeException('Expected query after \',\'');

            [$right, $rest] = $result;
            $nodes[] = $right;
        }

        return new QuerySet($nodes);
    }

    /**
     * @return array{Query, list<Token>}
     */
    private function tryConsumeQuery(array $tokens): ?array
    {
        $setResult = $this->tryConsumeSet($tokens);
        if (!$setResult) return null;

        [$set, $rest] = $setResult;

        $selectionResult = $this->tryConsumeSelection($rest);
        if ($selectionResult) [$selection, $rest] = $selectionResult;
        else $selection = null;

        return [new Query($set, $selection), $rest];
    }

    /**
     * @return array{Universe|Grouping, list<Token>}
     */
    private function tryConsumeSet(array $tokens): ?array
    {
        [$head, $tail] = h::decap($tokens);
        if (!$head) return null;

        if ($head->getType() === 'UNIVERSE') {
            return [new Universe(), $tail];
        }

        if ($head->getType() === 'L_PAREN') {
            return $this->consumeGrouping($tokens);
        }

        return null;
    }

    private function consumeGrouping(array $tokens): array
    {
        [$head, $tail] = h::decap($tokens);
        if ($head->getType() !== 'L_PAREN') throw new \RuntimeException('consumeGrouping called incorrectly');

        $queryResult = $this->tryConsumeQuery($tail);
        if (!$queryResult) throw new \RuntimeException('Expected a Query');
        
        [$query, $rest] = $queryResult;
        
        [$head, $tail] = h::decap($rest);
        if ($head->getType() !== 'R_PAREN') throw new \RuntimeException('Expected \')\'');

        return [new Grouping($query), $tail];
    }

    private function tryConsumeSelection(array $tokens): ?array
    {
        $result = $this->tryConsumeUnion($tokens);
        if (!$result) return null;

        [$union, $rest] = $result;
        return [$union, $rest];
    }

    private function tryConsumeUnion(array $tokens): ?array
    {
        $leftResult = $this->tryConsumeIntersection($tokens);
        if (!$leftResult) return null;

        [$left, $rest] = $leftResult;
        $nodes = [$left];

        while ($this->peek($rest)?->getType() === 'UNION') {
            [$head, $tail] = h::decap($rest);

            $rightResult = $this->tryConsumeIntersection($tail);
            if (!$rightResult) throw new \RuntimeException('Expected expression after \'|\'');

            [$right, $rest] = $rightResult;
            $nodes[] = $right;
        }

        return [new Union($nodes), $rest];
    }

    private function tryConsumeIntersection(array $tokens): ?array
    {
        $leftResult = $this->tryConsumeUnary($tokens);
        if (!$leftResult) return null;

        [$left, $rest] = $leftResult;
        $nodes = [$left];

        while ($this->peek($rest)?->getType() === 'INTERSECTION') {
            [$head, $tail] = h::decap($rest);

            $rightResult = $this->tryConsumeUnary($tail);
            if (!$rightResult) throw new \RuntimeException('Expected expression after \'&\'');

            [$right, $rest] = $rightResult;
            $nodes[] = $right;
        }

        return [new Intersection($nodes), $rest];
    }

    private function tryConsumeUnary(array $tokens): ?array
    {
        [$head, $tail] = h::decap($tokens);
        if (!$head) return null;

        if ($head->getType() === 'EXCLUSION') {
            $result = $this->tryConsumeUnary($tail);
            if (!$result) throw new \RuntimeException('Expected expression after \'!\'');

            [$expr, $rest] = $result;
            return [new Unary($expr), $rest];
        }

        $filterResult = $this->tryConsumeFilter($tokens);
        if (!$filterResult) return null;
        [$filter, $rest] = $filterResult;

        return [$filter, $rest];
    }

    private function tryConsumeFilter(array $tokens): ?array
    {
        [$head, $tail] = h::decap($tokens);
        if (!$head) return null;

        if ($head->getType() === 'FILTER') {
            [$predicate, $rest] = $this->consumeMetadataPredicate($tokens);
            return [new Filter($predicate), $rest];
        }
        
        $termResult = $this->tryConsumeTerm($tokens);
        if (!$termResult) return null;

        [$term, $rest] = $termResult;
        return [new Filter($term), $rest];
    }

    private function consumeMetadataPredicate(array $tokens): ?array
    {
        [$head, $tail] = h::decap($tokens);

        if ($head->getType() !== 'FILTER') {
            throw new \LogicException('consumeMetadataPredicate called without FILTER');
        }

        [$metaId, $rest] = $this->consumeMetaId($tail);
        [$comparison, $rest] = $this->consumeComparison($rest);
        
        $termResult = $this->tryConsumeTerm($rest);
        if (!$termResult) throw new \RuntimeException('Expected int|decmial|string or identifier for right side of comparison');
        [$term, $rest] = $termResult;

        return [new MetadataPredicate($metaId, $comparison, $term), $rest];
    }

    private function consumeComparison(array $tokens): array
    {
        [$head, $tail] = h::decap($tokens);

        $ast = match($head->getType()) {
            'COMPARISON' => new Comparison($head->getValue()),
            default => throw new \RuntimeException("Expected operator [<, <=, =, >=, >], found {$head->getType()}({$head->getValue()})")
        };

        return [$ast, $tail];
    }

    private function consumeMetaId(array $tokens): array
    {
        [$head, $tail] = h::decap($tokens);
        $ast = match($head->getType()) {
            'ATOM' => new MetadataIdentifier($head->getValue()),
            default => throw new \RuntimeException("Expected metadata identifier, found {$head->getType()}({$head->getValue()})")
        };
        return [$ast, $tail];
    }

    private function tryConsumeTerm(array $tokens): ?array
    {
        [$head, $tail] = h::decap($tokens);
        if (!$head) return null;

        $subjectResult = match ($head->getType()) {
            'ATOM'    => $this->tryConsumeId($tokens),
            'INTEGER',
            'DECIMAL',
            'STRING'  => $this->tryConsumeScalar($tokens),
            default   => null
        };

        if (!$subjectResult) return null;
        [$subject, $rest] = $subjectResult;
        return [new Term($subject), $rest];
    }

    private function tryConsumeId(array $tokens): ?array
    {
        [$head, $tail] = h::decap($tokens);
        if (!$head) return null;

        if ($head->getType() !== 'ATOM') return null;

        return [new Identifier($head->getValue()), $tail];
    }

    private function tryConsumeScalar(array $tokens): ?array
    {
        [$head, $tail] = h::decap($tokens);
        if (!$head) return null;

        return match ($head->getType()) {
            'INTEGER' => [new Scalar((int)$head->getValue()), $tail],
            'DECIMAL' => [new Scalar((float)$head->getValue()), $tail],
            'STRING'  => [new Scalar((string)$head->getValue()), $tail],
            default => null
        };
    }

    private function peek(array $tokens): ?Token
    {
        return $tokens[0] ?? null;
    }
}
