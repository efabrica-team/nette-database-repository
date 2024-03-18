<?php

namespace Efabrica\NetteRepository\Traits\KeepDefault;

use Efabrica\NetteRepository\Event\DeleteEventResponse;
use Efabrica\NetteRepository\Event\DeleteQueryEvent;
use Efabrica\NetteRepository\Event\InsertEventResponse;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteRepository\Event\QueryEvent;
use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Event\UpdateEventResponse;
use Efabrica\NetteRepository\Event\UpdateQueryEvent;
use Efabrica\NetteRepository\Repository\Entity;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteEventResponse;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteSubscriber;

final class KeepDefaultEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    public function supportsEvent(RepositoryEvent $event): bool
    {
        return $event->hasBehavior(KeepDefaultBehavior::class);
    }

    private function ensureDefault(RepositoryEvent $event, ?array $data = null): void
    {
        $repository = $event->getRepository();

        $behaviors = [];
        foreach ($repository->getBehaviors() as $behavior) {
            if ($behavior instanceof KeepDefaultBehavior) {
                $behaviors[] = $behavior;
            }
        }

        $batch = [];
        foreach ($behaviors as $behavior) {
            $defaultField = $behavior->getField();
            $query = $behavior->getQuery() ?? $repository->query();

            $defaultTrueQuery = (clone $query)->where([$defaultField => true]);
            $count = $defaultTrueQuery->count('*');
            if ($count === 1) {
                continue;
            }
            if ($count === 0) {
                if ($event instanceof QueryEvent) {
                    // KeepDefault event supports only single primary column, PRs are welcome
                    $primaryColumn = $repository->getPrimary()[0];
                    $eventQuery = (clone $event->getQuery())->select($primaryColumn);
                    $excludedEntity = (clone $query)->where("$primaryColumn NOT IN", $eventQuery)->first();
                    if ($excludedEntity instanceof Entity) {
                        $excludedEntity->$defaultField = true;
                        $batch[] = $excludedEntity;
                        continue;
                    }
                }
                $entity = $query->first();
                if ($entity instanceof Entity) {
                    $entity->$defaultField = true;
                    $batch[] = $entity;
                }
            } else { // $count > 1
                $primaryColumn = $repository->getPrimary()[0];
                if ($event instanceof QueryEvent) {
                    $eventQuery = (clone $event->getQuery())->select($primaryColumn);
                } else {
                    $eventQuery = $event->getRepository()->query()->select($primaryColumn);
                }
                // if $data[$defaultField] is true, then find first entity that is in event query and set all other entities to false
                if ($data[$defaultField] ?? false) {
                    $entity = (clone $query)->where("$primaryColumn IN", $eventQuery)->first() ?? $query->first();
                    if ($entity instanceof Entity) {
                        $query->where(["$primaryColumn !=" => $entity->getPrimary()])->scopeRaw()->update([$defaultField => false]);
                    }
                } else {
                    if (is_array($data)) {
                        // if $data[$defaultField] is false, then find first entity that is not in event query and set it to true
                        $entity = (clone $query)->where("$primaryColumn NOT IN", $eventQuery)->first() ?? $query->first();
                    } else {
                        $entity = $query->first();
                    }
                    if ($entity instanceof Entity) {
                        $entity->$defaultField = true;
                        $batch[] = $entity;
                    }
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

    public function onUpdate(UpdateQueryEvent $event, array &$data): UpdateEventResponse
    {
        $result = $event->handle($data);
        $repository = $event->getRepository();
        /** @var KeepDefaultBehavior $behavior */
        $behavior = $repository->getBehaviors()->get(KeepDefaultBehavior::class);
        if (!isset($data[$behavior->getField()])) {
            return $result;
        }
        $this->ensureDefault($event, $data);
        return $result;
    }

    public function onDelete(DeleteQueryEvent $event): DeleteEventResponse
    {
        $result = $event->handle();
        $this->ensureDefault($event);
        return $result;
    }

    public function onSoftDelete(SoftDeleteQueryEvent $event, array &$data): SoftDeleteEventResponse
    {
        return SoftDeleteEventResponse::fromUpdate($event, $this->onUpdate($event, $data));
    }
}
