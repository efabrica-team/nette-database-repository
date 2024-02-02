<?php

namespace Efabrica\NetteRepository\Event;

use LogicException;

class UpdateQueryEventResponse
{
    public function __construct(UpdateQueryEvent $event)
    {
        if (!$event->hasEnded()) {
            throw new LogicException('Event has not ended yet');
        }
    }
}
