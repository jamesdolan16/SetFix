# SetFix
** What regex is to strings, but for finite sets
## Indexed Attributes
SetFix can only filter on attributes that are explicitly indexed or exposed by the universe. Attempting to filter a non-indexed attribute will result in an error.
## Syntax
### Sets
```
*     : Select all elements in the universe
(...) : Control the operator precedence
```
### Set Operations
```
Use the indexed key for Selection
! : Exclusion
| : Union (OR)
& : Intersection (AND)
: : Predicate Filter
```
### Additional Operations
```
, : Concatination
```
## Examples
### Setup
```
PHP:

$set = [
    ['code' => 'D', 'name' => 'Dog', 'age' => 3],
    ['code' => 'C', 'name' => 'Cat', 'age' => 5],
    ['code' => 'F', 'name' => 'Frog', 'age' => 1]
];

$qr = SetFix::fromArray(
    set: $set,
    index: static fn(array $item) => $item['code']
    value: static fn(array $item) => $item['name']
);

$selection = $qr->query(...);
```
### Selection
```
*D|C            => ['Dog', 'Cat']
*:age<5         => ['Dog', 'Frog']
*:age>1&:age<5  => ['Dog']
(*C|F):age>1    => ['Cat']
*D,*D|C,*D|C|F  => [['Dog'], ['Dog', 'Cat'], ['Dog', 'Cat', 'Frog']]
```
## Grammar
querySet            := query (',' query)*
query               := set selection?
set                 := universe|grouping
grouping            := '(' query ')'
selection           := union
union               := intersection ('|' intersection)*
intersection        := unary ('&' unary)*
unary               := '!' unary | filter
filter              := metadataPredicate | term
metadataPredicate   := ':' metaId comparison term
term                := itemId | scalar
comparison          := < | <= | = | >= | >
universe            := *
scalar              := any_scalar_value

(QuerySet
  (Query
    (Set (Universe))
    (Selection
      (Union
        (Intersections
          (Intersection
            (Unaries
              (Filter
                (MetadataPredicate
                    (MetaId 'age')
                    (Comparison '>')
                    (Scalar 5)))
              (Filter
                (MetadataPredicate
                    (MetaId 'age')
                    (Comparison '<')
                    (Scalar 10))))))))))
            
