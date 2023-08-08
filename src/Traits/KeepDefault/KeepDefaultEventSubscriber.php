<?php

namespace Efabrica\NetteRepository\Traits\KeepDefault;

use Efabrica\NetteRepository\Event\DeleteQueryEvent;
use Efabrica\NetteRepository\Event\InsertEventResponse;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Event\UpdateQueryEvent;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteSubscriber;

final class KeepDefaultEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    public function supportsRepository(Repository $repository): bool
    {
        return $repository->behaviors()->has(KeepDefaultBehavior::class);
    }

    private function ensureDefault(RepositoryEvent $event): void
    {
        $repository = $event->getRepository();
        /** @var KeepDefaultBehavior $behavior */
        $behavior = $event->getBehaviors()->get(KeepDefaultBehavior::class);
        $defaultField = $behavior->getField();
        $query = $behavior->getQuery() ?? $repository->query();

        $countQuery = (clone $query)->where([$defaultField => true]);
        $count = $countQuery->count('*');
        if ($count === 1) {
            return;
        }
        if ($count === 0) {
            $entity = (clone $query)->limit(1)->fetch();
            if ($entity) {
                $repository->update($entity, [$defaultField => true]);
            }
        }

        // skip first record:
        $countQuery->fetch();
        // set all other records to false:
        while ($entity = $countQuery->fetch()) {
            $repository->update($entity, [$defaultField => false]);
        }
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
        $behavior = $repository->behaviors()->get(KeepDefaultBehavior::class);
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