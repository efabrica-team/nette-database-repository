<?php

namespace Efabrica\NetteDatabaseRepository\Subscriber;

use Efabrica\NetteDatabaseRepository\Event\DeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Event\InsertEventResponse;
use Efabrica\NetteDatabaseRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Event\SelectQueryEvent;
use Efabrica\NetteDatabaseRepository\Event\SelectQueryResponse;
use Efabrica\NetteDatabaseRepository\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\Inline\DeleteEventSubscriber;
use Efabrica\NetteDatabaseRepository\Subscriber\Inline\InsertEventSubscriber;
use Efabrica\NetteDatabaseRepository\Subscriber\Inline\SelectEventSubscriber;
use Efabrica\NetteDatabaseRepository\Subscriber\Inline\UpdateEventSubscriber;

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
