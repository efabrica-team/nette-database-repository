<?php

namespace Efabrica\NetteRepository\Subscriber\Inline;

use Efabrica\NetteRepository\Event\SelectEventResponse;
use Efabrica\NetteRepository\Event\SelectQueryEvent;

/**
 * Implement this in your repository to handle select events for a specific case.
 */
interface SelectEventSubscriber
{
    public function onSelect(SelectQueryEvent $event): SelectEventResponse;
}
