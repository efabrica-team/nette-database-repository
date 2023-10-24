<?php

namespace Efabrica\NetteRepository\Traits\SoftDelete;

use Efabrica\NetteRepository\Event\UpdateQueryEvent;

class SoftDeleteQueryEvent extends UpdateQueryEvent
{
    public function handle(array &$data): int
    {
        while ($subscriber = current($this->subscribers)) {
            next($this->subscribers);
            if ($subscriber instanceof SoftDeleteSubscriber && $subscriber->supportsEvent($this)) {
                return $subscriber->onSoftDelete($this, $data);
            }
        }
        return $this->query->scopeRaw()->update($data);
    }
}
