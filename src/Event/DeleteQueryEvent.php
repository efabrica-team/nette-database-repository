<?php

namespace Efabrica\NetteRepository\Event;

class DeleteQueryEvent extends QueryEvent
{
    public function handle(): DeleteEventResponse
    {
        while ($subscriber = current($this->subscribers)) {
            next($this->subscribers);
            if ($subscriber->supportsEvent($this)) {
                return $subscriber->onDelete($this);
            }
        }
        $this->ended = true;
        return new DeleteEventResponse($this, $this->query->scopeRaw()->delete());
    }

    public function stopPropagation(): DeleteEventResponse
    {
        $this->ended = true;
        return new DeleteEventResponse($this, 0);
    }
}
