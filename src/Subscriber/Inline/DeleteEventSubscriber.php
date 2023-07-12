<?php

namespace Efabrica\NetteDatabaseRepository\Subscriber\Inline;

use Efabrica\NetteDatabaseRepository\Event\DeleteQueryEvent;

interface DeleteEventSubscriber
{
    public function onDelete(DeleteQueryEvent $event): int;
}
