<?php

namespace Efabrica\NetteRepository\Traits\SoftDelete;

use Efabrica\NetteRepository\Event\DeleteEventResponse;
use Efabrica\NetteRepository\Event\DeleteQueryEvent;
use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Event\SelectEventResponse;
use Efabrica\NetteRepository\Event\SelectQueryEvent;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;

class SoftDeleteEventSubscriber extends EventSubscriber
{
    public function supportsEvent(RepositoryEvent $event): bool
    {
        return $event->hasBehavior(SoftDeleteBehavior::class);
    }

    public function onSelect(SelectQueryEvent $event): SelectEventResponse
    {
        /** @var SoftDeleteBehavior $behavior */
        $behavior = $event->getRepository()->getBehaviors()->get(SoftDeleteBehavior::class);
        if ($behavior->shouldFilterDeleted()) {
            $event->getQuery()->where($event->getRepository()->getTableName() . '.' . $behavior->getColumn(), null);
        }
        return $event->handle();
    }

    public function onDelete(DeleteQueryEvent $event): DeleteEventResponse
    {
        $behavior = $event->getBehavior(SoftDeleteBehavior::class);
        $data = [$behavior->getColumn() => $behavior->getNewValue()];
        foreach ($behavior->getUniqueColumns() as $uniqueColumn => $newValue) {
            $data[$uniqueColumn] = $newValue;
        }
        (new SoftDeleteQueryEvent($event->getQuery()))->handle($data);
        return $event->stopPropagation();
    }
}
