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

class UpdateEntitiesRepository extends Repository
{
    public UpdateEntitiesQuery $q;

    protected function setup(RepositoryBehaviors $b): void
    {
    }

    public function query(): Query
    {
        return $this->q ??= new UpdateEntitiesQuery($this);
    }
}

class UpdateEntitiesEntity extends Entity
{
}

class UpdateEntitiesQuery extends Query
{
    public array $updates = [];

    public function update(iterable $data, ?array $entities = null): int
    {
        $this->updates[] = ['data' => (array)$data, 'entities' => $entities];
        return 1;
    }
}

class UpdateEntitiesTest extends TestCase
{
    private function createRepo(): UpdateEntitiesRepository
    {
        $connection = new Connection('sqlite::memory:');
        $structure = new Structure($connection, new DevNullStorage());
        $explorer = new Explorer($connection, $structure, new StaticConventions(), new DevNullStorage());
        $container = new Container();
        $manager = new RepositoryManager($container);
        $scope = new ScopeContainer();
        $deps = new RepositoryDependencies($explorer, $container, $manager, $scope);
        return new UpdateEntitiesRepository('test', UpdateEntitiesEntity::class, UpdateEntitiesQuery::class, $deps);
    }

    public function testGroupedByUnsavedDiff(): void
    {
        $repo = $this->createRepo();
        $query = $repo->query();
        $e1 = $repo->createRow(['id' => 1, 'val' => 'a'], $query);
        $e2 = $repo->createRow(['id' => 2, 'val' => 'a'], $query);
        $e3 = $repo->createRow(['id' => 3, 'val' => 'b'], $query);
        $e1->val = 'x';
        $e2->val = 'x';
        $e3->val = 'y';

        $affected = $repo->updateEntities($e1, $e2, $e3);

        $this->assertSame(2, $affected);
        $this->assertCount(2, $query->updates);
        $this->assertSame(['val' => 'x'], $query->updates[0]['data']);
        $this->assertSame(['val' => 'y'], $query->updates[1]['data']);
        $this->assertSame([$e1], $query->updates[0]['entities']);
        $this->assertSame([$e3], $query->updates[1]['entities']);
    }
}

