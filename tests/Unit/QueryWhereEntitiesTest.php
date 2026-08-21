<?php

namespace Tests\Unit;

use Efabrica\NetteRepository\Repository\Entity;
use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Repository\RepositoryBehaviors;
use Efabrica\NetteRepository\Repository\RepositoryDependencies;
use Efabrica\NetteRepository\Repository\RepositoryManager;
use Efabrica\NetteRepository\Repository\Scope\ScopeContainer;
use Nette\Caching\Storages\DevNullStorage;
use Nette\Database\Connection;
use Nette\Database\Conventions\StaticConventions;
use Nette\Database\Explorer;
use Nette\Database\Structure;
use Nette\DI\Container;
use PHPUnit\Framework\TestCase;

class WhereEntitiesRepository extends Repository
{
    public WhereEntitiesQuery $q;

    protected function setup(RepositoryBehaviors $b): void
    {
    }

    public function query(): Query
    {
        return $this->q ??= new WhereEntitiesQuery($this);
    }
}

class WhereEntitiesEntity extends Entity
{
}

class WhereEntitiesQuery extends Query
{
    public array $whereCalls = [];

    public function where($condition, ...$params): static
    {
        $this->whereCalls[] = [$condition, $params];
        return $this;
    }
}

class QueryWhereEntitiesTest extends TestCase
{
    private function createRepo(): WhereEntitiesRepository
    {
        $connection = new Connection('sqlite::memory:');
        $structure = new Structure($connection, new DevNullStorage());
        $explorer = new Explorer($connection, $structure, new StaticConventions(), new DevNullStorage());
        $container = new Container();
        $manager = new RepositoryManager($container);
        $scope = new ScopeContainer();
        $deps = new RepositoryDependencies($explorer, $container, $manager, $scope);
        return new WhereEntitiesRepository('test', WhereEntitiesEntity::class, WhereEntitiesQuery::class, $deps);
    }

    public function testRespectsOriginalFlag(): void
    {
        $repo = $this->createRepo();
        $query = $repo->query();
        $e1 = $repo->createRow(['id' => 1], $query);
        $e2 = $repo->createRow(['id' => 2], $query);
        $e2->id = 5;

        $query->whereEntities([$e1, $e2], false);
        $this->assertSame([['id', [[1, 5]]]], $query->whereCalls);

        $query->whereCalls = [];
        $query->whereEntities([$e1, $e2], true);
        $this->assertSame([['id', [[1, 2]]]], $query->whereCalls);
    }
}

