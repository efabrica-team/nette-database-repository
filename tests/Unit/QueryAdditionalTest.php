<?php

namespace Tests\Unit;

use Efabrica\NetteRepository\Repository\Entity;
use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Repository\RepositoryBehaviors;
use Efabrica\NetteRepository\Repository\RepositoryDependencies;
use Efabrica\NetteRepository\Repository\RepositoryManager;
use Efabrica\NetteRepository\Repository\Scope\ScopeContainer;
use Nette\Application\BadRequestException;
use Nette\Caching\Storages\DevNullStorage;
use Nette\Database\Connection;
use Nette\Database\Conventions\StaticConventions;
use Nette\Database\Explorer;
use Nette\Database\Structure;
use Nette\DI\Container;
use PHPUnit\Framework\TestCase;

class SearchTestQuery extends Query
{
    public array $whereCalls = [];

    public function where($condition, ...$params): static
    {
        $this->whereCalls[] = [$condition, $params];
        return $this;
    }

    public function fetchAll(): array
    {
        return [];
    }
}

class SearchTestRepository extends Repository
{
    public SearchTestQuery $q;

    protected function setup(RepositoryBehaviors $b): void
    {
    }

    public function query(): Query
    {
        return $this->q ??= new SearchTestQuery($this);
    }
}

class FirstTestQuery extends Query
{
    public static array $fetchInfo = [];

    public function fetch(): ?Entity
    {
        self::$fetchInfo = ['limit' => $this->getLimit(), 'offset' => $this->getOffset()];
        return null;
    }
}

class FirstTestRepository extends Repository
{
    public FirstTestQuery $q;

    protected function setup(RepositoryBehaviors $b): void
    {
    }

    public function query(): Query
    {
        return $this->q ??= new FirstTestQuery($this);
    }
}

class FindRepoQuery extends Query
{
    public ?Entity $fetchReturn = null;

    public function fetch(): ?Entity
    {
        return $this->fetchReturn;
    }
}

class FindRepoEntity extends Entity
{
}

class FindRepoRepository extends Repository
{
    public FindRepoQuery $q;

    public function setMessage(string $message): void
    {
        $this->findOrFailMessage = $message;
    }

    protected function setup(RepositoryBehaviors $b): void
    {
    }

    public function query(): Query
    {
        return $this->q ??= new FindRepoQuery($this);
    }
}

class QueryAdditionalTest extends TestCase
{
    private function createRepo(string $repoClass): Repository
    {
        $connection = new Connection('sqlite::memory:');
        $structure = new Structure($connection, new DevNullStorage());
        $explorer = new Explorer($connection, $structure, new StaticConventions(), new DevNullStorage());
        $container = new Container();
        $manager = new RepositoryManager($container);
        $scope = new ScopeContainer();
        $deps = new RepositoryDependencies($explorer, $container, $manager, $scope);
        if ($repoClass === SearchTestRepository::class) {
            return new SearchTestRepository('test', FindRepoEntity::class, SearchTestQuery::class, $deps);
        }
        if ($repoClass === FirstTestRepository::class) {
            return new FirstTestRepository('test', FindRepoEntity::class, FirstTestQuery::class, $deps);
        }
        return new FindRepoRepository('test', FindRepoEntity::class, FindRepoQuery::class, $deps);
    }

    public function testSearchCreatesWhereClause(): void
    {
        /** @var SearchTestRepository $repo */
        $repo = $this->createRepo(SearchTestRepository::class);
        $query = $repo->query();
        $query->search(['foo', 'bar'], 'abc');
        $this->assertStringContainsString(
            'WHERE (([foo] LIKE ?) OR ([bar] LIKE ?))',
            $query->getSql()
        );
    }

    public function testFirstUsesLimitOne(): void
    {
        /** @var FirstTestRepository $repo */
        $repo = $this->createRepo(FirstTestRepository::class);
        $query = $repo->query()->limit(10, 5);
        FirstTestQuery::$fetchInfo = [];
        $query->first();
        $this->assertSame(['limit' => 1, 'offset' => 5], FirstTestQuery::$fetchInfo);
        $this->assertSame(10, $query->getLimit());
        $this->assertSame(5, $query->getOffset());
    }

    public function testFindOrFailThrowsException(): void
    {
        /** @var FindRepoRepository $repo */
        $repo = $this->createRepo(FindRepoRepository::class);
        $repo->setMessage('missing');
        $repo->query()->fetchReturn = null;
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('missing');
        $repo->findOrFail(1);
    }

    public function testFindOrFailReturnsEntity(): void
    {
        /** @var FindRepoRepository $repo */
        $repo = $this->createRepo(FindRepoRepository::class);
        $entity = $repo->createRow(['id' => 1], $repo->query());
        $repo->query()->fetchReturn = $entity;
        $this->assertSame($entity, $repo->findOrFail(1));
    }
}

