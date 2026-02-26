# Doctrine ORM Extender

Extension library for Doctrine ORM that builds `QueryBuilder`/`Query` from a declarative ruleset.

## What It Does

- Creates a `QueryBuilder` from `RuleSetInterface`
- Applies supported Doctrine expressions (`where`, `orderBy`, `groupBy`, `join`, `select`)
- Binds parameters from the ruleset
- Returns either a ready `QueryBuilder` or a final `Query`

## Requirements

- PHP `^8.1`
- `doctrine/orm` `^2.10|^3.0`

## Install

```bash
composer require evyex/doctrine-orm-extender
```

## Example

Without ruleset object, query logic is usually assembled inline in repository/service methods.
With this package, the same logic can be moved to a dedicated business ruleset class:

```php
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Evyex\DoctrineOrmExtender\QueryFactory\RuleSetInterface;

final class ClientGroupDogs implements RuleSetInterface
{
    public function __construct(private int $clientGroupId)
    {
    }

    public function getEntityClass(): string
    {
        return Animal::class;
    }

    public function getRootAlias(): string
    {
        return 'a';
    }

    public function getRules(): array
    {
        return [
            new Expr\Join(Expr\Join::INNER_JOIN, 'a.client', 'c'),
            new Expr\Andx(['c.group = :clientGroupId']),
            new Expr\Andx(['a.type = :animalType']),
        ];
    }

    public function getParameters(): ArrayCollection
    {
        return new ArrayCollection([
            new Parameter('clientGroupId', $this->clientGroupId),
            new Parameter('animalType', 'dog'),
        ]);
    }
}
```

Then usage becomes explicit and reusable:

```php
$dogs = $queryFactory
    ->createQuery(new ClientGroupDogs(5))
    ->getResult();
```
