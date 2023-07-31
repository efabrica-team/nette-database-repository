<?php

namespace Efabrica\NetteRepository\Subscriber\Inline;

use Efabrica\NetteRepository\Event\DeleteQueryEvent;

/**
 * Implement this in your repository to handle delete events for a specific case.
 */
interface DeleteEventSubscriber
{
    public function onDelete(DeleteQueryEvent $event): int;
}
