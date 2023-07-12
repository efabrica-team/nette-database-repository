<?php

namespace Efabrica\NetteDatabaseRepository\Subscriber\Inline;

use Efabrica\NetteDatabaseRepository\Event\UpdateQueryEvent;

interface UpdateEventSubscriber
{
    public function onUpdate(UpdateQueryEvent $event, array &$data): int;
}
