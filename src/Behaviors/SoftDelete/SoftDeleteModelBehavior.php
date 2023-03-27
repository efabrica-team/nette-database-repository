<?php

namespace Efabrica\NetteDatabaseRepository\Behaviors\SoftDelete;

use BadMethodCallException;
use Efabrica\NetteDatabaseRepository\Exceptions\MissingRepositoryException;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Repositores\Repository;

trait SoftDeleteModelBehavior
{
    public function isDeleted(): bool
    {
        $repository = $this->getSoftDeleteRepository();
        return $this->{$repository->deletedAtField()} !== null;
    }

    public function delete(): int
    {
        $hookIgnores = $this->getHookIgnores();
        $this->resetHookIgnores();

        $repository = $this->getSoftDeleteRepository();

        return (int)$repository->importHookIgnores($hookIgnores)->delete($this);
    }

    public function restore(): int
    {
        $hookIgnores = $this->getHookIgnores();
        $this->resetHookIgnores();

        $repository = $this->getSoftDeleteRepository();

        return (int)$repository->importHookIgnores($hookIgnores)->restore($this);
    }

    public function forceDelete(): int
    {
        $hookIgnores = $this->getHookIgnores();
        $this->resetHookIgnores();

        $repository = $this->getSoftDeleteRepository();

        return (int)$repository->importHookIgnores($hookIgnores)->forceDelete($this);
    }

    /**
     * @return Repository&SoftDeleteBehavior
     * @throws MissingRepositoryException
     */
    private function getSoftDeleteRepository(): Repository
    {
        /**
         * @var ActiveRow $this
         * @var Repository&SoftDeleteBehavior $repository
         */
        $repository = $this->repositoryManager->createForTable($this->table->getName());
        if ($repository === null) {
            throw new MissingRepositoryException('Model with ' . SoftDeleteModelBehavior::class . ' must have a repository');
        }
        if (!in_array(SoftDeleteBehavior::class, class_uses($repository), true)) {
            throw new BadMethodCallException('Repository "' . get_class($repository) . '" must use "' . SoftDeleteBehavior::class . '".');
        }
        return $repository;
    }
}
