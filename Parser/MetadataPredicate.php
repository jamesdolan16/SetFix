<?php declare(strict_types=1);

namespace SetFix\Parser;

class MetadataPredicate
{
    public function __construct(
        private MetadataIdentifier $metaId,
        private Comparison $comparison,
        private Term $term
    ){}

    public function getMetaId(): MetadataIdentifier
    {
        return $this->metaId;
    }

    public function getComparison(): Comparison
    {
        return $this->comparison;
    }

    public function getTerm(): Term
    {
        return $this->term;
    }

    public function toString(int $depth)
    {
        return "\n" . str_repeat(' ', $depth * 2) . "(MetadataPredicate" . 
            $this->metaId->toString($depth + 1) .
            $this->comparison->toString($depth + 1) .
            $this->term->toString($depth + 1) . ")";
    }
}