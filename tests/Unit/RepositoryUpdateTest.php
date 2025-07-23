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

class UpdateTestRepository extends Repository
{
    public UpdateTestQuery $q;

    protected function setup(RepositoryBehaviors $b): void
    {
    }

    public function query(): Query
    {
        return $this->q ??= new UpdateTestQuery($this);
    }
}

class UpdateTestEntity extends Entity
{
}

class UpdateTestQuery extends Query
{
    public array $wherePrimaryCalls = [];
    public array $whereCalls = [];
    public array $updates = [];

    public function wherePrimary($key): static
    {
        $this->wherePrimaryCalls[] = $key;
        return $this;
    }

    public function where($condition, ...$params): static
    {
        $this->whereCalls[] = [$condition, $params];
        return $this;
    }

    public function update(iterable $data, ?array $entities = null): int
    {
        $this->updates[] = ['data' => (array)$data, 'entities' => $entities];
        return 1;
    }
}

class RepositoryUpdateTest extends TestCase
{
    private function createRepo(): UpdateTestRepository
    {
        $connection = new Connection('sqlite::memory:');
        $structure = new Structure($connection, new DevNullStorage());
        $explorer = new Explorer($connection, $structure, new StaticConventions(), new DevNullStorage());
        $container = new Container();
        $manager = new RepositoryManager($container);
        $scope = new ScopeContainer();
        $deps = new RepositoryDependencies($explorer, $container, $manager, $scope);
        return new UpdateTestRepository('test', UpdateTestEntity::class, UpdateTestQuery::class, $deps);
    }

    public function testUpdateWithScalarId(): void
    {
        $repo = $this->createRepo();
        $repo->update(5, ['foo' => 'bar']);
        $query = $repo->query();
        $this->assertSame([5], $query->wherePrimaryCalls);
        $this->assertSame([['data' => ['foo' => 'bar'], 'entities' => null]], $query->updates);
    }

    public function testUpdateWithEntity(): void
    {
        $repo = $this->createRepo();
        $query = $repo->query();
        $entity = $repo->createRow(['id' => 1, 'foo' => 'a'], $query);
        $repo->update($entity, ['foo' => 'b']);
        $this->assertSame([['data' => ['foo' => 'b'], 'entities' => [$entity]]], $query->updates);
    }

    public function testUpdateWithArrayConditions(): void
    {
        $repo = $this->createRepo();
        $repo->update(['foo' => 'x'], ['bar' => 'y']);
        $query = $repo->query();
        $this->assertSame([[['foo' => 'x'], []]], array_map(fn($c) => [$c[0], $c[1]], $query->whereCalls));
        $this->assertSame([['data' => ['bar' => 'y'], 'entities' => null]], $query->updates);
    }
}

