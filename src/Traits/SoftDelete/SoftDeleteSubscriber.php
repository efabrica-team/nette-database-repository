<?php

namespace Efabrica\NetteDatabaseRepository\Traits\SoftDelete;

interface SoftDeleteSubscriber
{
    public function onSoftDelete(SoftDeleteQueryEvent $event, array &$data): int;
}
