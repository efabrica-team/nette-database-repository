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
use Nette\InvalidStateException;
use PHPUnit\Framework\TestCase;

class PrimaryTestRepository extends Repository
{
    public PrimaryTestQuery $q;

    protected function setup(RepositoryBehaviors $b): void
    {
    }

    public function query(): Query
    {
        return $this->q ??= new PrimaryTestQuery($this);
    }
}

class PrimaryTestEntity extends Entity
{
}

class PrimaryTestQuery extends Query
{
}

class EntityPrimaryTest extends TestCase
{
    private function createRepo(): PrimaryTestRepository
    {
        $connection = new Connection('sqlite::memory:');
        $structure = new Structure($connection, new DevNullStorage());
        $explorer = new Explorer($connection, $structure, new StaticConventions(), new DevNullStorage());
        $container = new Container();
        $manager = new RepositoryManager($container);
        $scope = new ScopeContainer();
        $deps = new RepositoryDependencies($explorer, $container, $manager, $scope);
        return new PrimaryTestRepository('test', PrimaryTestEntity::class, PrimaryTestQuery::class, $deps);
    }

    public function testGetPrimaryAndSignature(): void
    {
        $repo = $this->createRepo();
        $entity = $repo->createRow(['id' => 1]);
        $this->assertSame(1, $entity->getPrimary());
        $this->assertSame('1', $entity->getSignature());

        $entity->id = 2;
        $this->assertSame(1, $entity->getPrimary());
        $this->assertSame(2, $entity->getPrimary(true, false));
        $this->assertSame('2', $entity->getSignature(true, false));
    }

    public function testGetPrimaryMissing(): void
    {
        $repo = $this->createRepo();
        $entity = $repo->createRow([]);
        $this->assertNull($entity->getPrimary(false));
        $this->expectException(InvalidStateException::class);
        $entity->getPrimary();
    }
}
