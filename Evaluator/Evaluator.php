<?php declare(strict_types=1);

namespace SetFix\Evaluator;

use Closure;
use SetFix\Parser\Filter;
use SetFix\Parser\Query;
use SetFix\Parser\QuerySet;
use SetFix\Parser\Universe;
use SetFix\Parser\Grouping;
use SetFix\Parser\Identifier;
use SetFix\Parser\Intersection;
use SetFix\Parser\MetadataPredicate;
use SetFix\Parser\Scalar;
use SetFix\Parser\Term;
use SetFix\Parser\Unary;
use SetFix\Parser\Union;

class Evaluator
{
    public function __construct(
        private array $universe,
        private Closure $identifierCallback,
        private bool $debug = false
        //private Closure $value
    ){}

    public function evaluate(QuerySet $querySet): array
    {
        $evaluated = $this->evaluateQuerySet($querySet);
        if (count($evaluated) === 1) $evaluated = $evaluated[0];
        return $evaluated;
    }

    private function evaluateQuerySet(QuerySet $querySet): array
    {
        if ($this->debug) echo $querySet->toString(0);
        return array_map(fn(Query $query) => $this->evaluateQuery($query), $querySet->getQuerySet());
    }

    private function evaluateQuery(Query $query): array
    {
        $set = $this->evaluateSet($query->getSet());
        if ($query->getUnion()) return $this->evaluateUnion($query->getUnion(), $set);
        return $set;
    }

    private function evaluateSet(Universe|Grouping $set): array
    {
        if ($set instanceof Universe) return $this->universe;

        return $this->evaluateGrouping($set);
    }

    private function evaluateGrouping(Grouping $grouping): array
    {
        return $this->evaluateQuery($grouping->getQuery());
    }

    private function evaluateUnion(Union $union, array $set): array
    {
        return array_merge(
            ...array_map(fn($u) => $this->evaluateIntersection($u, $set), $union->getIntersections())
        );
    }

    private function evaluateIntersection(Intersection $intersection, array $set): array
    {
        return $this->setIntersect(
            ...array_map(fn(Unary|Filter $u) => match(true) {
                $u instanceof Unary => $this->evaluateUnary($u, $set),
                $u instanceof Filter => $this->evaluateFilter($u, $set)
            }, $intersection->getUnaries())
        );
    }

    /**
     * Calculate items in $first that are also in $second 
     * 
     * @return list<array|object>
     */
    private function setIntersect(array $first, ?array $second = null): array
    {
        if (!$second) return $first;
        return array_reduce($first, 
            fn(array $carry, array|object $fItem) => array_any($second, 
                fn(array|object $sItem) => 
                    ($this->identifierCallback)($sItem) === ($this->identifierCallback)($fItem)
            ) ? [...$carry, $fItem] : $carry, 
        []);
    }

    private function evaluateUnary(Unary $unary, array $set): array
    {
        return $this->setDiff($set, $this->evaluateFilter($unary->getChild(), $set));
    }

    /**
     * Calculate items in $first that are missing from $second 
     * 
     * @return list<array|object>
     */
    private function setDiff(array $first, array $second): array
    {
        return array_reduce($first, 
            fn(array $carry, array|object $fItem) => !array_any($second, 
                fn(array|object $sItem) => 
                    ($this->identifierCallback)($sItem) === ($this->identifierCallback)($fItem)
            ) ? [...$carry, $fItem] : $carry, 
        []);
    }

    private function evaluateFilter(Filter $filter, array $set): array
    {
        $child = $filter->getChild();
        return match(true) {
            $child instanceof MetadataPredicate => $this->evaluateMetadataPredicate($child, $set),
            $child instanceof Term => $this->evaluateTerm($child, $set)
        };
    }

    public function evaluateMetadataPredicate(MetadataPredicate $predicate, array $set): array
    {
        $metaId = $predicate->getMetaId()->getValue();
        $comparison = $predicate->getComparison()->getOperation();
        $evaluatedTerm = $this->evaluateTerm($predicate->getTerm(), $set);
        
        $termValue = match(true) {
            $predicate->getTerm()->getChild() instanceof Identifier => $evaluatedTerm[0][$metaId],
            $predicate->getTerm()->getChild() instanceof Scalar => $evaluatedTerm
        };
        
        return match($comparison) {
            '<' => array_filter($set, fn($item) => $item[$metaId] < $termValue),
            '<=' => array_filter($set, fn($item) => $item[$metaId] <= $termValue),
            '=' => array_filter($set, fn($item) => $item[$metaId] === $termValue),
            '>' => array_filter($set, fn($item) => $item[$metaId] > $termValue),
            '>=' => array_filter($set, fn($item) => $item[$metaId] >= $termValue),
        };
    }

    private function evaluateTerm(Term $term, array $set): array|int|float|string
    {
        $child = $term->getChild();
        return match(true) {
            $child instanceof Identifier => [$this->getItemById($child->getValue(), $set)],
            $child instanceof Scalar => $child->getValue()
        };
    }

    private function getItemById(string $id, array $set): array
    {
        return array_find($set, fn($item) => ($this->identifierCallback)($item) === $id);
    }
}