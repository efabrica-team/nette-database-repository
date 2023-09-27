<?php

namespace Efabrica\NetteRepository\Traits\SoftDelete;

use Efabrica\NetteRepository\Event\DeleteQueryEvent;
use Efabrica\NetteRepository\Event\SelectQueryEvent;
use Efabrica\NetteRepository\Event\SelectQueryResponse;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;

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
        if ($behavior->shouldFilterDeleted()) {
            $event->getQuery()->where($event->getRepository()->getTableName() . '.' . $behavior->getColumn(), null);
        }
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
