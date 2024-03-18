<?php

namespace Efabrica\NetteRepository\Subscriber;

use Efabrica\NetteRepository\Event\DeleteEventResponse;
use Efabrica\NetteRepository\Event\DeleteQueryEvent;
use Efabrica\NetteRepository\Event\InsertEventResponse;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Event\SelectEventResponse;
use Efabrica\NetteRepository\Event\SelectQueryEvent;
use Efabrica\NetteRepository\Event\UpdateEventResponse;
use Efabrica\NetteRepository\Event\UpdateQueryEvent;
use Efabrica\NetteRepository\Repository\Entity;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Subscriber\Inline\DeleteEventSubscriber;
use Efabrica\NetteRepository\Subscriber\Inline\InsertEventSubscriber;
use Efabrica\NetteRepository\Subscriber\Inline\SelectEventSubscriber;
use Efabrica\NetteRepository\Subscriber\Inline\UpdateEventSubscriber;

abstract class EventSubscriber implements InsertEventSubscriber, UpdateEventSubscriber, DeleteEventSubscriber, SelectEventSubscriber
{
    abstract public function supportsEvent(RepositoryEvent $event): bool;

    /**
     * @return InsertEventResponse returned by $event->handle() or $event->stopPropagation()
     */
    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        return $event->handle();
    }

    /**
     * @return UpdateEventResponse returned by $event->handle() or $event->stopPropagation()
     */
    public function onUpdate(UpdateQueryEvent $event, array &$data): UpdateEventResponse
    {
        return $event->handle($data);
    }

    /**
     * @return DeleteEventResponse returned by $event->handle() or $event->stopPropagation()
     */
    public function onDelete(DeleteQueryEvent $event): DeleteEventResponse
    {
        return $event->handle();
    }

    /**
     * @return SelectEventResponse returned by $event->handle() or $event->stopPropagation()
     */
    public function onSelect(SelectQueryEvent $event): SelectEventResponse
    {
        return $event->handle();
    }

    /**
     * Called when an entity is loaded from the database.
     * Be aware that calling createRow() here might cause an infinite loop.
     */
    public function onLoad(Entity $entity, Repository $repository): void
    {
    }
}
