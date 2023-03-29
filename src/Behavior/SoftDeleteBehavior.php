<?php

namespace Efabrica\NetteDatabaseRepository\Behavior;

use DateTime;
use DateTimeInterface;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Repositores\Repository;
use Nette\Database\Table\Selection;

class SoftDeleteBehavior extends Behavior
{
    private string $deletedAt;

    private Repository $repository;

    private bool $isDefaultWhere = true;
    /**
     * @var bool|DateTimeInterface
     */
    private $newValue;

    public function __construct(Repository $repository, string $deletedAt = 'deleted_at', $newValue = null)
    {
        $this->deletedAt = $deletedAt;
        $this->repository = $repository;
        $this->newValue = $newValue ?? new DateTime();
    }

    public function setIsDefaultWhere(bool $isDefaultWhere): void
    {
        $this->isDefaultWhere = $isDefaultWhere;
    }

    public function beforeDelete(ActiveRow $row): ?bool
    {
        foreach ($this->repository->getBehaviors() as $behavior) {
            if ($behavior instanceof BehaviorWithSoftDelete) {
                $behavior->beforeSoftDelete($row);
            }
        }
        $this->repository->raw()->update($row, [$this->deletedAt => $this->newValue]);
        foreach ($this->repository->getBehaviors() as $behavior) {
            if ($behavior instanceof BehaviorWithSoftDelete) {
                $behavior->afterSoftDelete($row);
            }
        }
        return true;
    }

    public function beforeSelect(Selection $selection): void
    {
        if ($this->isDefaultWhere) {
            $selection->where($this->deletedAt . ' IS NOT NULL', new DateTime());
        }
    }


    public function restore(ActiveRow $row): void
    {
        $this->repository->getExplorer()->transaction(function () use ($row) {
            foreach ($this->repository->getBehaviors() as $behavior) {
                if ($behavior instanceof BehaviorWithSoftDelete) {
                    $behavior->beforeRestore($row);
                }
            }
            $this->repository->raw()->update($row, [$this->deletedAt => null]);
            foreach ($this->repository->getBehaviors() as $behavior) {
                if ($behavior instanceof BehaviorWithSoftDelete) {
                    $behavior->afterRestore($row);
                }
            }
        });
    }
}
