<?php

namespace Efabrica\NetteRepository\Subscriber\Inline;

use Efabrica\NetteRepository\Event\InsertEventResponse;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;

/**
 * Implement this in your repository to handle insert events for a specific case.
 */
interface InsertEventSubscriber
{
    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse;
}
