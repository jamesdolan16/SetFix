# SetFix
*What regex is to strings, but for finite sets*

## Syntax
### Sets
```
*     : Select all elements in the universe, this set is called Universe
(...) : Create a set from the inner query, this set is called a grouping 
```
### Set Operations
```
! : Exclusion (NOT)
| : Union (OR)
& : Intersection (AND)
: : Filter by metadata predicate
```
### Filter Operations
```
<,<=,=,>=,> : Standard comparison operations
```
### Additional Operations
```
, : Query Concatenation
```
## Examples
### Setup
```php
$set = [
    ['code' => 'D', 'name' => 'Dog', 'age' => 3],
    ['code' => 'C', 'name' => 'Cat', 'age' => 5],
    ['code' => 'F', 'name' => 'Frog', 'age' => 1]
];

$qr = SetFix::fromArray(
    set: $set,
    identifierCallback: static fn(array $item) => $item['code']
);

$selection = $qr->query(...);
```
### Queries
```
*D|C            => ['Dog', 'Cat']
*:age<5         => ['Dog', 'Frog']
*:age>1&:age<5  => ['Dog']
(*C|F):age>1    => ['Cat']
*D,*D|C,*D|C|F  => [['Dog'], ['Dog', 'Cat'], ['Dog', 'Cat', 'Frog']]
```
## Grammar
```
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
scalar              := string | float | int
string              := '"' (a-zA-Z_-.)* '"' 
float               := int '.' (0-9)+
int                 := '-'? (0-9)*
```
## Debug
Debug output can be activated by passing true to the debug flag when instantiating an **Evaluator**, this causes the **Evaluator** to print the AST for the parsed query in Lisp format.
```clojure
(QuerySet
  (Query
    (Universe)
    (Union
      (Intersection
        (Filter
          (MetadataPredicate
            (MetadataIdentifier "value")
            (Comparison =)
            (Term
              (Scalar "Boo"))))
      (Intersection
        (Filter
          (MetadataPredicate
            (MetadataIdentifier "value")
            (Comparison =)
            (Term
              (Scalar "Abra")))))))
```
            
