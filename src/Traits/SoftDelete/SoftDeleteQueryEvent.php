<?php

namespace Efabrica\NetteRepository\Traits\SoftDelete;

use Efabrica\NetteRepository\Event\UpdateQueryEvent;

class SoftDeleteQueryEvent extends UpdateQueryEvent
{
    #[\Override]
    public function handle(array &$data): SoftDeleteEventResponse
    {
        while ($subscriber = current($this->subscribers)) {
            next($this->subscribers);
            if ($subscriber instanceof SoftDeleteSubscriber && $subscriber->supportsEvent($this)) {
                return $subscriber->onSoftDelete($this, $data);
            }
        }
        $this->ended = true;
        return new SoftDeleteEventResponse($this, $this->query->scopeRaw()->update($data));
    }
}
