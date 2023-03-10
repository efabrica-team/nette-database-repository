<?php

namespace Efabrica\NetteDatabaseRepository\Behavior;

use DateTime;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Repositores\Repository;
use Nette\Database\Table\Selection;

class SoftDeleteBehavior extends Behavior
{
    private string $deletedAt;
    private Repository $repository;
    private bool $defaultWhere;

    public function __construct(Repository $repository, bool $defaultWhere = true, string $deletedAt = 'deleted_at')
    {
        $this->deletedAt = $deletedAt;
        $this->defaultWhere = $defaultWhere;
        $this->repository = $repository;
    }

    public function setDefaultWhere(bool $defaultWhere): void
    {
        $this->defaultWhere = $defaultWhere;
    }

    public function beforeDelete(ActiveRow $row): ?bool
    {
        foreach ($this->repository->getBehaviors() as $behavior) {
            if ($behavior instanceof BehaviorWithSoftDelete) {
                $behavior->beforeSoftDelete($row);
            }
        }
        $this->repository->raw()->update($row, [$this->deletedAt => new DateTime()]);
        foreach ($this->repository->getBehaviors() as $behavior) {
            if ($behavior instanceof BehaviorWithSoftDelete) {
                $behavior->afterSoftDelete($row);
            }
        }
        return true;
    }

    public function beforeSelect(Selection $selection): void
    {
        if ($this->defaultWhere) {
            $selection->where($this->deletedAt.' < ?', new DateTime());
        }
    }
}