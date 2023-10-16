<?php

namespace Efabrica\NetteRepository\Event;

class SelectQueryEvent extends QueryEvent
{
    public function handle(): SelectQueryResponse
    {
        while ($subscriber = current($this->subscribers)) {
            next($this->subscribers);
            if ($subscriber->supportsEvent($this)) {
                return $subscriber->onSelect($this);
            }
        }
        return $this->stopPropagation();
    }

    public function stopPropagation(): SelectQueryResponse
    {
        $this->subscribers = [];
        return new SelectQueryResponse($this);
    }
}
