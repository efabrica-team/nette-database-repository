<?php

namespace Efabrica\NetteDatabaseRepository\Subscriber\Inline;

use Efabrica\NetteDatabaseRepository\Event\InsertEventResponse;
use Efabrica\NetteDatabaseRepository\Event\InsertRepositoryEvent;

interface InsertEventSubscriber
{
    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse;
}
