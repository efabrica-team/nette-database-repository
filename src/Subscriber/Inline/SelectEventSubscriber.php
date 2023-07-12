<?php

namespace Efabrica\NetteDatabaseRepository\Subscriber\Inline;

use Efabrica\NetteDatabaseRepository\Event\SelectQueryEvent;
use Efabrica\NetteDatabaseRepository\Event\SelectQueryResponse;

interface SelectEventSubscriber
{
    public function onSelect(SelectQueryEvent $event): SelectQueryResponse;
}
