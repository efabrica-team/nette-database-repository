<?php

namespace Efabrica\NetteRepository\Subscriber\Inline;

use Efabrica\NetteRepository\Event\SelectQueryEvent;
use Efabrica\NetteRepository\Event\SelectQueryResponse;

/**
 * Implement this in your repository to handle select events for a specific case.
 */
interface SelectEventSubscriber
{
    public function onSelect(SelectQueryEvent $event): SelectQueryResponse;
}
