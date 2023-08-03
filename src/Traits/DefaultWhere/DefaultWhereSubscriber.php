<?php

namespace Efabrica\NetteRepository\Traits\DefaultWhere;

use Efabrica\NetteRepository\Event\SelectQueryEvent;
use Efabrica\NetteRepository\Event\SelectQueryResponse;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;

class DefaultWhereSubscriber extends EventSubscriber
{
    public function onSelect(SelectQueryEvent $event): SelectQueryResponse
    {
        foreach ($event->getBehaviors()->all() as $behavior) {
            if ($behavior instanceof DefaultWhereBehavior) {
                $behavior->apply($event->getQuery());
            }
        }
        return $event->handle();
    }
}
