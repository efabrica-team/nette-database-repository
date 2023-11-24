<?php

namespace Efabrica\NetteRepository\Traits\KeepDefault;

use Efabrica\NetteRepository\Event\DeleteQueryEvent;
use Efabrica\NetteRepository\Event\InsertEventResponse;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Event\UpdateQueryEvent;
use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteSubscriber;

final class KeepDefaultEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    public function supportsEvent(RepositoryEvent $event): bool
    {
        return $event->hasBehavior(KeepDefaultBehavior::class);
    }

    private function ensureDefault(RepositoryEvent $event): void
    {
        $repository = $event->getRepository();
        $behaviors = $event->getBehaviors();
        $batch = [];
        foreach ($behaviors as $behavior) {
            if (!$behavior instanceof KeepDefaultBehavior) {
                continue;
            }
            $defaultField = $behavior->getField();
            $query = $behavior->getQuery() ?? $repository->query();

            $defaultTrueQuery = (clone $query)->where([$defaultField => true]);
            $count = $defaultTrueQuery->count('*');
            if ($count === 1) {
                return;
            }
            if ($count === 0) {
                $entity = $query->first();
                if ($entity instanceof Entity) {
                    $entity->$defaultField = true;
                    $batch[] = $entity;
                }
            } else {
                // skip first record:
                $defaultTrueQuery->fetch();
                // set all other records to false:
                while ($entity = $defaultTrueQuery->fetch()) {
                    $entity->$defaultField = false;
                    $batch[] = $entity;
                }
            }
        }
        $repository->updateEntities(...$batch);
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        $result = $event->handle();
        $this->ensureDefault($event);
        return $result;
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        $result = $event->handle($data);
        $repository = $event->getRepository();
        /** @var KeepDefaultBehavior $behavior */
        $behavior = $repository->getBehaviors()->get(KeepDefaultBehavior::class);
        if (!isset($data[$behavior->getField()])) {
            return $result;
        }
        $this->ensureDefault($event);
        return $result;
    }

    public function onDelete(DeleteQueryEvent $event): int
    {
        $result = $event->handle();
        $this->ensureDefault($event);
        return $result;
    }

    public function onSoftDelete(SoftDeleteQueryEvent $event, array &$data): int
    {
        return $this->onUpdate($event, $data);
    }
}
