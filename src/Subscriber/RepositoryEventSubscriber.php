<?php

namespace Efabrica\NetteRepository\Subscriber;

use Efabrica\NetteRepository\Event\DeleteQueryEvent;
use Efabrica\NetteRepository\Event\InsertEventResponse;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Event\SelectQueryEvent;
use Efabrica\NetteRepository\Event\SelectQueryResponse;
use Efabrica\NetteRepository\Event\UpdateQueryEvent;
use Efabrica\NetteRepository\Subscriber\Inline\DeleteEventSubscriber;
use Efabrica\NetteRepository\Subscriber\Inline\InsertEventSubscriber;
use Efabrica\NetteRepository\Subscriber\Inline\SelectEventSubscriber;
use Efabrica\NetteRepository\Subscriber\Inline\UpdateEventSubscriber;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteSubscriber;

class RepositoryEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    public function supportsEvent(RepositoryEvent $event): bool
    {
        $repository = $event->getRepository();
        return $repository instanceof InsertEventSubscriber
            || $repository instanceof UpdateEventSubscriber
            || $repository instanceof DeleteEventSubscriber
            || $repository instanceof SelectEventSubscriber
            || $repository instanceof SoftDeleteSubscriber;
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

    public function onSoftDelete(SoftDeleteQueryEvent $event, array &$data): int
    {
        $repository = $event->getRepository();
        if ($repository instanceof SoftDeleteSubscriber) {
            return $repository->onSoftDelete($event, $data);
        }
        return $event->handle($data);
    }
}
