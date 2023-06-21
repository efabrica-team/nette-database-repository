<?php

namespace Efabrica\NetteDatabaseRepository\Subscriber;

use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\DeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertEventResponse;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\SelectQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\SelectQueryResponse;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\UpdateQueryEvent;

abstract class EventSubscriber
{
    /**
     * @param Repository $repository
     * @return bool should the event subscriber be used for this repository?
     * If it returns false, the event subscriber will not be added to the repository.
     * @see Events::forRepository()
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
}
