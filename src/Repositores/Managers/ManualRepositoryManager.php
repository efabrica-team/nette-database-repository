<?php

namespace Efabrica\NetteDatabaseRepository\Repositores\Managers;

use Efabrica\NetteDatabaseRepository\Repositores\Repository;

final class ManualRepositoryManager implements RepositoryManagerInterface
{
    private array $repositories = [];

    public function addRepository(string $table, Repository $repository): self
    {
        $this->repositories[$table] = $repository;
        return $this;
    }

    public function unsetRepository(string $table): self
    {
        unset($this->repositories[$table]);
        return $this;
    }

    /**
     * @param Repository[] $repositories
     */
    public function setRepositories(array $repositories): self
    {
        $this->repositories = $repositories;
        return $this;
    }

    public function createForTable(string $table): ?Repository
    {
        return $this->repositories[$table] ?? null;
    }
}
