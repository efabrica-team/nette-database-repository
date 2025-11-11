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

class DummyRepository extends Repository
{
    public DummyQuery $q;

    protected function setup(RepositoryBehaviors $b): void
    {
    }

    #[\Override]
    public function query(): Query
    {
        return $this->q ??= new DummyQuery($this);
    }
}

class DummyEntity extends Entity
{
}

class DummyQuery extends Query
{
    public array $inserted = [];
    public array $updated = [];

    #[\Override]
    public function insert(iterable $data): Entity|array|int|bool
    {
        $this->inserted = (array)$data;
        return 1;
    }

    #[\Override]
    public function update(iterable $data, ?array $entities = null): int
    {
        $this->updated = (array)$data;
        return 1;
    }
}

class EntitySaveUpdateTest extends TestCase
{
    private function createRepo(): DummyRepository
    {
        $connection = new Connection('sqlite::memory:');
        $structure = new Structure($connection, new DevNullStorage());
        $explorer = new Explorer($connection, $structure, new StaticConventions(), new DevNullStorage());
        $container = new Container();
        $manager = new RepositoryManager($container);
        $scope = new ScopeContainer();
        $deps = new RepositoryDependencies($explorer, $container, $manager, $scope);
        return new DummyRepository('test', DummyEntity::class, DummyQuery::class, $deps);
    }

    public function testUpdateFiltersDiff(): void
    {
        $repo = $this->createRepo();
        $query = $repo->query();
        $entity = $repo->createRow(['id' => 1, 'foo' => 'bar'], $query);
        $entity->foo = 'bar';
        $this->assertSame(['foo' => 'bar'], $entity->unsavedChanges());
        $entity->update();
        $this->assertSame([], $query->updated);
        $this->assertSame([], $entity->unsavedChanges());
    }

    public function testUpdateSendsChangedValue(): void
    {
        $repo = $this->createRepo();
        $query = $repo->query();
        $entity = $repo->createRow(['id' => 1, 'foo' => 'bar'], $query);
        $entity->foo = 'baz';
        $entity->update();
        $this->assertSame(['foo' => 'baz'], $query->updated);
    }
}
