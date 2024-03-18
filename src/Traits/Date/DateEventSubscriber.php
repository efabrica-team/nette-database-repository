<?php

namespace Efabrica\NetteRepository\Traits\Date;

use DateTimeImmutable;
use Efabrica\NetteRepository\Event\InsertEventResponse;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Event\UpdateEventResponse;
use Efabrica\NetteRepository\Event\UpdateQueryEvent;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteEventResponse;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteSubscriber;

final class DateEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    public function supportsEvent(RepositoryEvent $event): bool
    {
        return $event->hasBehavior(DateBehavior::class);
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        /** @var DateBehavior $behavior */
        $behavior = $event->getBehaviors()->get(DateBehavior::class);
        $createdAt = $behavior->getCreatedAtField();
        $updatedAt = $behavior->getUpdatedAtField();
        foreach ($event->getEntities() as $entity) {
            if (isset($createdAt) && !isset($entity[$createdAt])) {
                $entity[$createdAt] = new DateTimeImmutable();
            }
            if (isset($updatedAt) && !isset($entity[$updatedAt])) {
                $entity[$updatedAt] = new DateTimeImmutable();
            }
        }
        return $event->handle();
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): UpdateEventResponse
    {
        /** @var DateBehavior $behavior */
        $behavior = $event->getBehaviors()->get(DateBehavior::class);
        $updatedAt = $behavior->getUpdatedAtField();
        if (isset($updatedAt) && !isset($data[$updatedAt])) {
            $data[$updatedAt] = new DateTimeImmutable();
        }
        return $event->handle($data);
    }

    public function onSoftDelete(SoftDeleteQueryEvent $event, array &$data): SoftDeleteEventResponse
    {
        return SoftDeleteEventResponse::fromUpdate($event, $this->onUpdate($event, $data));
    }
}
