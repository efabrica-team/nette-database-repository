<?php

namespace Efabrica\NetteRepository\Traits\SoftDelete;

interface SoftDeleteSubscriber
{
    public function onSoftDelete(SoftDeleteQueryEvent $event, array &$data): int;
}
