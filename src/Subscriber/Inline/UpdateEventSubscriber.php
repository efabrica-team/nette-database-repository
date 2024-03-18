<?php

namespace Efabrica\NetteRepository\Subscriber\Inline;

use Efabrica\NetteRepository\Event\UpdateEventResponse;
use Efabrica\NetteRepository\Event\UpdateQueryEvent;

/**
 * Implement this in your repository to handle update events for a specific case.
 */
interface UpdateEventSubscriber
{
    public function onUpdate(UpdateQueryEvent $event, array &$data): UpdateEventResponse;
}
