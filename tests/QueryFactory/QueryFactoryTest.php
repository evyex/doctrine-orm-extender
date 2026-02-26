<?php

declare(strict_types=1);

namespace Evyex\DoctrineOrmExtender\Tests\QueryFactory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\QueryBuilder;
use Evyex\DoctrineOrmExtender\QueryFactory\QueryFactory;
use Evyex\DoctrineOrmExtender\QueryFactory\QueryFactoryUnexpectedValueException;
use Evyex\DoctrineOrmExtender\QueryFactory\RuleSetInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(QueryFactory::class)]
final class QueryFactoryTest extends TestCase
{
    public function testCreateQueryReturnsQueryFromQueryBuilder(): void
    {
        $parameters = new ArrayCollection([new Parameter('id', 1)]);
        $ruleSet = new TestRuleSet('Entity\User', 'u', [], $parameters);

        $query = $this->createStub(Query::class);

        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['from', 'select', 'setParameters', 'getQuery'])
            ->getMock()
        ;

        $qb->expects(self::once())
            ->method('from')
            ->with('Entity\User', 'u')
            ->willReturnSelf()
        ;

        $qb->expects(self::once())
            ->method('select')
            ->with('u')
            ->willReturnSelf()
        ;

        $qb->expects(self::once())
            ->method('setParameters')
            ->with($parameters)
            ->willReturnSelf()
        ;

        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query)
        ;

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb)
        ;

        $factory = new QueryFactory($em);

        self::assertSame($query, $factory->createQuery($ruleSet));
    }

    public function testCreateQueryBuilderAppliesSupportedRules(): void
    {
        $rules = [
            new Expr\Andx(['u.id = :id']),
            new Expr\OrderBy('u.id', 'DESC'),
            new Expr\GroupBy(['u.status']),
            new Expr\Select(['u.id']),
        ];

        $parameters = new ArrayCollection([new Parameter('id', 1)]);
        $ruleSet = new TestRuleSet('Entity\User', 'u', $rules, $parameters);

        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['from', 'select', 'add', 'setParameters'])
            ->getMock()
        ;

        $qb->expects(self::once())
            ->method('from')
            ->with('Entity\User', 'u')
            ->willReturnSelf()
        ;

        $qb->expects(self::once())
            ->method('select')
            ->with('u')
            ->willReturnSelf()
        ;

        $addCalls = [];
        $qb->expects(self::exactly(4))
            ->method('add')
            ->willReturnCallback(function (string $part, object $value) use (&$addCalls, $qb): QueryBuilder {
                $addCalls[] = [$part, $value::class];

                return $qb;
            })
        ;

        $qb->expects(self::once())
            ->method('setParameters')
            ->with($parameters)
            ->willReturnSelf()
        ;

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb)
        ;

        $factory = new QueryFactory($em);
        $actualQb = $factory->createQueryBuilder($ruleSet);

        self::assertSame($qb, $actualQb);
        self::assertSame(
            [
                ['where', Expr\Andx::class],
                ['orderBy', Expr\OrderBy::class],
                ['groupBy', Expr\GroupBy::class],
                ['select', Expr\Select::class],
            ],
            $addCalls
        );
    }

    public function testCreateQueryBuilderAppliesInnerJoinRule(): void
    {
        $rule = new Expr\Join(
            Expr\Join::INNER_JOIN,
            'u.posts',
            'p',
            Expr\Join::WITH,
            'p.enabled = true',
            'p.id'
        );
        $ruleSet = new TestRuleSet('Entity\User', 'u', [$rule], new ArrayCollection());

        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['from', 'select', 'innerJoin', 'setParameters'])
            ->getMock()
        ;

        $qb->method('from')->willReturnSelf();
        $qb->method('select')->willReturnSelf();

        $qb->expects(self::once())
            ->method('innerJoin')
            ->with('u.posts', 'p', Expr\Join::WITH, 'p.enabled = true', 'p.id')
            ->willReturnSelf()
        ;

        $qb->expects(self::once())
            ->method('setParameters')
            ->with(self::isInstanceOf(ArrayCollection::class))
            ->willReturnSelf()
        ;

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb)
        ;

        $factory = new QueryFactory($em);
        $factory->createQueryBuilder($ruleSet);
    }

    public function testCreateQueryBuilderAppliesLeftJoinRule(): void
    {
        $rule = new Expr\Join(
            Expr\Join::LEFT_JOIN,
            'u.profile',
            'profile',
            Expr\Join::WITH,
            'profile.user = u'
        );
        $ruleSet = new TestRuleSet('Entity\User', 'u', [$rule], new ArrayCollection());

        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['from', 'select', 'leftJoin', 'setParameters'])
            ->getMock()
        ;

        $qb->method('from')->willReturnSelf();
        $qb->method('select')->willReturnSelf();

        $qb->expects(self::once())
            ->method('leftJoin')
            ->with('u.profile', 'profile', Expr\Join::WITH, 'profile.user = u', null)
            ->willReturnSelf()
        ;

        $qb->expects(self::once())
            ->method('setParameters')
            ->with(self::isInstanceOf(ArrayCollection::class))
            ->willReturnSelf()
        ;

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb)
        ;

        $factory = new QueryFactory($em);
        $factory->createQueryBuilder($ruleSet);
    }

    public function testCreateQueryBuilderThrowsOnUnsupportedRule(): void
    {
        $ruleSet = new TestRuleSet('Entity\User', 'u', [new \stdClass()], new ArrayCollection());

        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['from', 'select'])
            ->getMock()
        ;

        $qb->expects(self::once())
            ->method('from')
            ->with('Entity\User', 'u')
            ->willReturnSelf()
        ;
        $qb->expects(self::once())
            ->method('select')
            ->with('u')
            ->willReturnSelf()
        ;

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb)
        ;

        $factory = new QueryFactory($em);

        $this->expectException(QueryFactoryUnexpectedValueException::class);
        $this->expectExceptionMessage('Wrong rule on 0');

        $factory->createQueryBuilder($ruleSet);
    }

    public function testCreateQueryBuilderThrowsWhenJoinAliasIsMissing(): void
    {
        $rule = new Expr\Join(Expr\Join::INNER_JOIN, 'u.posts', null);
        $ruleSet = new TestRuleSet('Entity\User', 'u', [$rule], new ArrayCollection());

        $qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['from', 'select'])
            ->getMock()
        ;

        $qb->expects(self::once())
            ->method('from')
            ->with('Entity\User', 'u')
            ->willReturnSelf()
        ;
        $qb->expects(self::once())
            ->method('select')
            ->with('u')
            ->willReturnSelf()
        ;

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb)
        ;

        $factory = new QueryFactory($em);

        $this->expectException(QueryFactoryUnexpectedValueException::class);
        $this->expectExceptionMessage('Join alias cannot be null');

        $factory->createQueryBuilder($ruleSet);
    }
}

final class TestRuleSet implements RuleSetInterface
{
    /** @param array<mixed> $rules */
    public function __construct(
        private readonly string $entityClass,
        private readonly string $rootAlias,
        private readonly array $rules,
        private readonly ArrayCollection $parameters,
    ) {}

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function getRootAlias(): string
    {
        return $this->rootAlias;
    }

    public function getParameters(): ArrayCollection
    {
        return $this->parameters;
    }
}
