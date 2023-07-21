<?php

namespace Efabrica\NetteDatabaseRepository\Traits\SoftDelete;

use Efabrica\NetteDatabaseRepository\Event\DeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Event\SelectQueryEvent;
use Efabrica\NetteDatabaseRepository\Event\SelectQueryResponse;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;

class SoftDeleteEventSubscriber extends EventSubscriber
{
    public function supportsRepository(Repository $repository): bool
    {
        return $repository->behaviors()->has(SoftDeleteBehavior::class);
    }

    public function onSelect(SelectQueryEvent $event): SelectQueryResponse
    {
        /** @var SoftDeleteBehavior $behavior */
        $behavior = $event->getRepository()->behaviors()->get(SoftDeleteBehavior::class);
        $event->getQuery()->where(
            $event->getRepository()->getTableName() . '.' . $behavior->getColumn() . ' = NULL',
            false
        );
        return $event->handle();
    }

    public function onDelete(DeleteQueryEvent $event): int
    {
        /** @var SoftDeleteBehavior $behavior */
        $behavior = $event->getRepository()->behaviors()->get(SoftDeleteBehavior::class);
        $data = [$behavior->getColumn() => $behavior->getNewValue()];
        return (new SoftDeleteQueryEvent($event->getQuery()))->handle($data);
    }
}
