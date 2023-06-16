<?php

namespace Efabrica\NetteDatabaseRepository\Traits\SoftDelete;

interface SoftDeleteSubscriber
{
    public function softDelete(SoftDeleteQueryEvent $event, array &$data): int;
}
