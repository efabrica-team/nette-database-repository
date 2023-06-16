<?php

namespace Efabrica\NetteDatabaseRepository\Subscriber\Event;

use LogicException;

abstract class EntityEventResponse
{
    public function __construct(RepositoryEvent $event)
    {
        if (!$event->hasEnded()) {
            throw new LogicException('Event has not ended yet');
        }
    }

}
