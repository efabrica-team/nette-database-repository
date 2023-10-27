<?php

namespace Efabrica\NetteRepository\Traits\DefaultOrder;

use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Event\SelectQueryEvent;
use Efabrica\NetteRepository\Event\SelectQueryResponse;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;

class DefaultOrderEventSubscriber extends EventSubscriber
{
    public function supportsEvent(RepositoryEvent $event): bool
    {
        return $event->hasBehavior(DefaultOrderBehavior::class);
    }

    public function onSelect(SelectQueryEvent $event): SelectQueryResponse
    {
        $event->getBehavior(DefaultOrderBehavior::class)->apply($event->getQuery());
        return $event->handle();
    }
}
