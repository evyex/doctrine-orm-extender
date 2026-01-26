<?php

declare(strict_types=1);

namespace Evyex\DoctrineOrmExtender\QueryFactory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Expr\Base;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\Query\Parameter;

interface RuleSetInterface
{
    /**
     * @return class-string
     */
    public function getEntityClass(): string;

    /**
     * @return array<Base|Join|OrderBy>
     */
    public function getRules(): array;

    public function getRootAlias(): string;

    /**
     * @return ArrayCollection<int, Parameter>
     */
    public function getParameters(): ArrayCollection;
}
