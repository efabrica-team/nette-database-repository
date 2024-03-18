<?php

namespace Efabrica\NetteRepository\Event;

class SelectQueryEvent extends QueryEvent
{
    public function handle(): SelectEventResponse
    {
        while ($subscriber = current($this->subscribers)) {
            next($this->subscribers);
            if ($subscriber->supportsEvent($this)) {
                return $subscriber->onSelect($this);
            }
        }
        return $this->stopPropagation();
    }

    public function stopPropagation(): SelectEventResponse
    {
        $this->ended = true;
        return new SelectEventResponse($this);
    }
}
