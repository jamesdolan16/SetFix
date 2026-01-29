# SetFix
A macro-extensible algebra for querying and constructing finite sets

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
;   : Expression Termination
:=  : Assignment
::  : Signature
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
## Selection
```
*D|C            => ['Dog', 'Cat']
*:age<5         => ['Dog', 'Frog']
*:age>1&:age<5  => ['Dog']
(*C|F):age>1    => ['Cat']
*D,*D|C,*D|C|F  => [['Dog'], ['Dog', 'Cat'], ['Dog', 'Cat', 'Frog']]
```
## Grammar
```
querySet            := query (';' query)*
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
## The Future of SetFix
### Named Sets
Named Sets are a proposed feature to allow for more composable query definitions. You can assign any valid set a name
using the new assignment operator `:=`. Your named set can then be used many times within further queries.

Named Sets can make use of other named sets to achieve optimal composability, see below:
```
children := *:age<16;
boys := children:gender='male';
boys;
```
Note that Named Sets act as AST node substitutions and thus are evaluated lazily. See how they fit into an AST below:
```clojure
(Statement
  (NamedSet
    (SetIdentifier "children")
    (Query
      (Universe)
      (Union
        (Intersection
          (Filter
            (MetadataPredicate
              (MetadataIdentifier "age")
              (Comparison <)
              (Term
                (Scalar 16))))))))
  (NamedSet
    (SetIdentifier "boys")
    (Query
      (SetIdentifier "children")
      (Union
        (Intersection
          (Filter
            (MetadataPredicate
              (MetadataIdentifier "gender")
              (Comparison =)
              (Term
                (Scalar "male"))))))))
  (QuerySet
    (Query
      (SetIdentifier "boys"))))
```
Resolves to
```clojure
(Statement
  (NamedSet
    (SetIdentifier "boys")
    (Query
      (Grouping
        (Query
          (Universe)
          (Union
            (Intersection
              (Filter
                (MetadataPredicate
                  (MetadataIdentifier "age")
                  (Comparison <)
                  (Term
                    (Scalar 16))))))))
      (Union
        (Intersection
          (Filter
            (MetadataPredicate
              (MetadataIdentifier "gender")
              (Comparison =)
              (Term
                (Scalar "male"))))))))
  (QuerySet
    (Query
      (SetIdentifier "boys"))))
```
Resolves to
```clojure
(Statement
  (QuerySet
    (Query
      (Grouping
        (Query
          (Grouping
            (Query
              (Universe)
              (Union
                (Intersection
                  (Filter
                    (MetadataPredicate
                      (MetadataIdentifier "age")
                      (Comparison <)
                      (Term
                        (Scalar 16))))))))
          (Union
            (Intersection
              (Filter
                (MetadataPredicate
                  (MetadataIdentifier "gender")
                  (Comparison =)
                  (Term
                    (Scalar "male")))))))))))
```
### Macros
Macros are a proposed feature that will further improve composability in SetFix. Unlike Named Sets, Macros do not need
to be valid Sets, they can be anything thereby facilitating arbitrary code reuse. Additionally Macros can take
parameters, introducing interesting new ways to write SetFix code.

Macros can be defined using the new Signature operator `::`. The syntax for defining a Macro is as follows:
```
macroId (paramId ' ')* :: body;
```
Below are a few examples of Macro definitions:
```
old :: :age>60;
between metaId a b :: :metaId>a&:metaId<b;
inSchool :: [between age 4 16];
*[inSchool]
```
Notice the square-bracket syntax in the above example, this is SetFix' Macro calling syntax, it is formalised below:
```
[macroId (param ' ')*]
```
### Extended Set Operations
#### Shift
Shift allows you to drop the first, or the last n elements from a set as follows:
```
(*A|B|C)>>1 # Evaluates to [B, C]
(*A|B|C)<<1 # Evaluates to [A, B]
```
#### Stride
Stride allows you to select only every nth element from a set, see below:
```
(*A|B|C)~2  # Evaluates to [A, C]
(*A|B)~2    # Evaluates to [A]
(*A|B|C)~1  # Evaluates to [A, B, C]
(*A|B|C)~0  # Evaluates to []
```
### Constructions
Constructions are a proposed feature that will facilitate structured output from SetFix queries. New stuff:
#### Primitive Construction
The fundamental building block of constructions is Primitive Construction, this is achieved with the following syntax:
```
{Set? (' ' Set)*}
```
Example
```
{"This" "Is" "A" "Construction"}  # Evaluates to ["This", "Is", "A", "Construction"]
{(*:age<10) (*:age>5)}
```
#### Zip Operator `/`
The Zip operator takes the lefthand set and combines it with the righthand set on an positional level to produce pairs.
Note that the lefthand set and the righthand set must have the same number of items otherwise the operation is invalid
and an error will be thrown. The syntax is as follows:
```
Set/Set
```
Example
```
(*A|B|C)/(*D|E|F) # Evaluates to [[A,D], [B,E], [C,F]]
```
An huge benefit provided by the Zip Operator is apparent when you marry it with Macros and Extended Set Operations, below is the implementation of the `kv` Macro (Key-Value):
```
kv items :: keys := (items~2)/((items>>1)~2)
```
This macro allows for the easy creation of nested constructions:
```
[kv
  "heightGrouped" [kv "tall" tall "short" short]
  "all" juniors]
```
Resolves to
```
{"heightGrouped" "all"}/{{"tall" "short"}/{tall short} juniors}
```
Which evaluates to the following, that can easily be converted to proper host language dictionaries/hashmaps
```
[
  ["heightGrouped, [["tall", tall], ["short", short]]],
  ["all", juniors]
]
```  
#### Product Operator `//`

    
