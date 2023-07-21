<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Cast;

use Efabrica\NetteDatabaseRepository\Event\InsertEventResponse;
use Efabrica\NetteDatabaseRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;

final class CastEventSubscriber extends EventSubscriber
{
    public function supportsRepository(Repository $repository): bool
    {
        return $repository->behaviors()->has(CastBehavior::class);
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
