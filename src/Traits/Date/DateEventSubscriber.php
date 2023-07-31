<?php

namespace Efabrica\NetteRepository\Traits\Date;

use DateTimeImmutable;
use Efabrica\NetteRepository\Event\InsertEventResponse;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteRepository\Event\UpdateQueryEvent;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteSubscriber;

class DateEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    public function supportsRepository(Repository $repository): bool
    {
        return $repository->behaviors()->has(DateBehavior::class);
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        /** @var DateBehavior $behavior */
        $behavior = $event->getBehaviors()->get(DateBehavior::class);
        $createdAt = $behavior->getCreatedAtField();
        $updatedAt = $behavior->getUpdatedAtField();
        foreach ($event->getEntities() as $entity) {
            if (!isset($entity[$createdAt])) {
                $entity[$createdAt] = new DateTimeImmutable();
            }
            if (!isset($entity[$updatedAt])) {
                $entity[$updatedAt] = new DateTimeImmutable();
            }
        }
        return $event->handle();
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        /** @var DateBehavior $behavior */
        $behavior = $event->getBehaviors()->get(DateBehavior::class);
        $updatedAt = $behavior->getUpdatedAtField();
        if (!isset($data[$updatedAt])) {
            $data[$updatedAt] = new DateTimeImmutable();
        }
        return $event->handle($data);
    }

    public function onSoftDelete(SoftDeleteQueryEvent $event, array &$data): int
    {
        return $this->onUpdate($event, $data);
    }
}
