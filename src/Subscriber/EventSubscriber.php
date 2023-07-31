<?php

namespace Efabrica\NetteRepository\Subscriber;

use Efabrica\NetteRepository\Event\DeleteQueryEvent;
use Efabrica\NetteRepository\Event\InsertEventResponse;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteRepository\Event\SelectQueryEvent;
use Efabrica\NetteRepository\Event\SelectQueryResponse;
use Efabrica\NetteRepository\Event\UpdateQueryEvent;
use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Subscriber\Inline\DeleteEventSubscriber;
use Efabrica\NetteRepository\Subscriber\Inline\InsertEventSubscriber;
use Efabrica\NetteRepository\Subscriber\Inline\SelectEventSubscriber;
use Efabrica\NetteRepository\Subscriber\Inline\UpdateEventSubscriber;

abstract class EventSubscriber implements InsertEventSubscriber, UpdateEventSubscriber, DeleteEventSubscriber, SelectEventSubscriber
{
    /**
     * @param Repository $repository
     * @return bool should the event subscriber be used for this repository?
     * If it returns false, the event subscriber will not be added to the repository.
     * @see RepositoryEvents::forRepository()
     */
    public function supportsRepository(Repository $repository): bool
    {
        return true;
    }

    /**
     * @return InsertEventResponse returned by $event->handle() or $event->stopPropagation()
     */
    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        return $event->handle();
    }

    /**
     * @return int number of affected rows. returned by $event->handle($event->getData())
     */
    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        return $event->handle($data);
    }

    /**
     * @return int number of affected rows. returned by $event->handle()
     */
    public function onDelete(DeleteQueryEvent $event): int
    {
        return $event->handle();
    }

    /**
     * @return SelectQueryResponse returned by $event->handle() or $event->stopPropagation()
     */
    public function onSelect(SelectQueryEvent $event): SelectQueryResponse
    {
        return $event->handle();
    }

    /**
     * Called when an entity is loaded from the database.
     */
    public function onLoad(Entity $entity, Repository $repository): void
    {
    }
}
