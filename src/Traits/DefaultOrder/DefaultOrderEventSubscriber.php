<?php

namespace Efabrica\NetteRepository\Traits\DefaultOrder;

use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Event\SelectEventResponse;
use Efabrica\NetteRepository\Event\SelectQueryEvent;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;

class DefaultOrderEventSubscriber extends EventSubscriber
{
    public function supportsEvent(RepositoryEvent $event): bool
    {
        return $event->hasBehavior(DefaultOrderBehavior::class);
    }

    #[\Override]
    public function onSelect(SelectQueryEvent $event): SelectEventResponse
    {
        $event->getBehavior(DefaultOrderBehavior::class)->apply($event->getQuery());
        return $event->handle();
    }
}
