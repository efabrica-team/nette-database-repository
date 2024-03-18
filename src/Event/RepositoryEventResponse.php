<?php

namespace Efabrica\NetteRepository\Event;

use LogicException;

class RepositoryEventResponse
{
    public function __construct(RepositoryEvent $event)
    {
        if (!$event->hasEnded()) {
            throw new LogicException('Event has not ended yet');
        }
    }
}
