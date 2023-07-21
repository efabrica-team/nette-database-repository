<?php

namespace Efabrica\NetteDatabaseRepository\Subscriber;

use Efabrica\NetteDatabaseRepository\Event\DeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Event\InsertEventResponse;
use Efabrica\NetteDatabaseRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Event\SelectQueryEvent;
use Efabrica\NetteDatabaseRepository\Event\SelectQueryResponse;
use Efabrica\NetteDatabaseRepository\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\Inline\DeleteEventSubscriber;
use Efabrica\NetteDatabaseRepository\Subscriber\Inline\InsertEventSubscriber;
use Efabrica\NetteDatabaseRepository\Subscriber\Inline\SelectEventSubscriber;
use Efabrica\NetteDatabaseRepository\Subscriber\Inline\UpdateEventSubscriber;

class RepositoryEventSubscriber extends EventSubscriber
{
    public function supportsRepository(Repository $repository): bool
    {
        return $repository instanceof InsertEventSubscriber
            || $repository instanceof UpdateEventSubscriber
            || $repository instanceof DeleteEventSubscriber
            || $repository instanceof SelectEventSubscriber;
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        $repository = $event->getRepository();
        if ($repository instanceof InsertEventSubscriber) {
            return $repository->onInsert($event);
        }
        return $event->handle();
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        $repository = $event->getRepository();
        if ($repository instanceof UpdateEventSubscriber) {
            return $repository->onUpdate($event, $data);
        }
        return $event->handle($data);
    }

    public function onDelete(DeleteQueryEvent $event): int
    {
        $repository = $event->getRepository();
        if ($repository instanceof DeleteEventSubscriber) {
            return $repository->onDelete($event);
        }
        return $event->handle();
    }

    public function onSelect(SelectQueryEvent $event): SelectQueryResponse
    {
        $repository = $event->getRepository();
        if ($repository instanceof SelectEventSubscriber) {
            return $repository->onSelect($event);
        }
        return $event->handle();
    }
}
