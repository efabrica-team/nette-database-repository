<?php

namespace Efabrica\NetteRepository\Traits\Cast;

use Efabrica\NetteRepository\Event\InsertEventResponse;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Event\UpdateQueryEvent;
use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;

final class CastEventSubscriber extends EventSubscriber
{
    public function supportsEvent(RepositoryEvent $event): bool
    {
        return $event->hasBehavior(CastBehavior::class);
    }

    public function onLoad(Entity $entity, Repository $repository): void
    {
        foreach ($repository->behaviors()->all() as $behavior) {
            if ($behavior instanceof CastBehavior) {
                foreach ($behavior->getFields() as $field) {
                    if (isset($entity[$field])) {
                        $entity[$field] = $behavior->decodeFromDB($entity[$field]);
                    }
                }
            }
        }
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        foreach ($event->getBehaviors()->all() as $behavior) {
            if ($behavior instanceof CastBehavior) {
                foreach ($behavior->getFields() as $field) {
                    foreach ($event->getEntities() as $entity) {
                        if (isset($entity[$field])) {
                            $entity[$field] = $behavior->encodeForDB($entity[$field]);
                        }
                    }
                }
            }
        }
        return $event->handle();
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        foreach ($event->getBehaviors()->all() as $behavior) {
            if ($behavior instanceof CastBehavior) {
                foreach ($behavior->getFields() as $field) {
                    if (isset($data[$field])) {
                        $data[$field] = $behavior->encodeForDB($data[$field]);
                    }
                }
            }
        }
        return $event->handle($data);
    }
}
