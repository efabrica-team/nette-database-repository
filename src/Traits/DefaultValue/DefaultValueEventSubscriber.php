<?php

namespace Efabrica\NetteRepository\Traits\DefaultValue;

use Efabrica\NetteRepository\Event\InsertEventResponse;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;

final class DefaultValueEventSubscriber extends EventSubscriber
{
    public function supportsRepository(Repository $repository): bool
    {
        return $repository->behaviors()->has(DefaultValueBehavior::class);
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        foreach ($event->getBehaviors()->all() as $behavior) {
            if ($behavior instanceof DefaultValueBehavior) {
                $field = $behavior->getField();
                $value = $behavior->getValue();
                foreach ($event->getEntities() as $entity) {
                    if (!isset($entity[$field])) {
                        $entity[$field] = $value;
                    }
                }
            }
        }
        return $event->handle();
    }
}
