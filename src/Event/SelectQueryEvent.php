<?php

namespace Efabrica\NetteRepository\Event;

use Efabrica\NetteRepository\Subscriber\EventSubscriber;

class SelectQueryEvent extends QueryEvent
{
    public function handle(): SelectQueryResponse
    {
        $subscriber = current($this->subscribers);
        next($this->subscribers);
        if ($subscriber instanceof EventSubscriber) {
            return $subscriber->onSelect($this);
        }
        return $this->stopPropagation();
    }

    public function stopPropagation(): SelectQueryResponse
    {
        $this->subscribers = [];
        return new SelectQueryResponse($this);
    }
}