<?php

namespace Efabrica\NetteDatabaseRepository\Behaviors\SoftDelete;

use BadMethodCallException;
use Efabrica\NetteDatabaseRepository\Behaviors\SelectionBehavior;
use Efabrica\NetteDatabaseRepository\Exceptions\MissingRepositoryException;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Repositores\Repository;
use Nette\Utils\DateTime;

trait SoftDeleteSelectionBehavior
{
    use SelectionBehavior;

    /**
     * @return static
     */
    public function withTrashed(): self
    {
        return $this->ignoreBehavior(SoftDeleteBehavior::class, null, 'defaultConditionsWhereNotDeleted');
    }

    /**
     * @return static
     * @throws MissingRepositoryException
     */
    public function onlyTrashed(): self
    {
        $repository = $this->getSoftDeleteRepository();

        return $this
            ->ignoreBehavior(SoftDeleteBehavior::class, null, 'defaultConditionsWhereNotDeleted')
            ->where($repository->deletedAtField() . ' NOT NULL');
    }

    public function delete(): int
    {
        $repository = $this->getSoftDeleteRepository();
        return $this->update([
            $repository->deletedAtField() => new DateTime(),
        ]);
    }

    public function restore(): int
    {
        $repository = $this->getSoftDeleteRepository();
        return $this->update([
            $repository->deletedAtField() => null,
        ]);
    }

    public function forceDelete(): int
    {
        return parent::delete();
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
        $repository = $this->repositoryManager->createForTable($this->getName());
        if ($repository === null) {
            throw new MissingRepositoryException('Selection with ' . SoftDeleteSelectionBehavior::class . ' must have a repository');
        }
        if (!in_array(SoftDeleteBehavior::class, class_uses($repository), true)) {
            throw new BadMethodCallException('Repository "' . get_class($repository) . '" must use "' . SoftDeleteBehavior::class . '".');
        }
        return $repository;
    }
}
