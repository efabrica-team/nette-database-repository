<?php

namespace Efabrica\NetteRepository\Event;

use Efabrica\NetteRepository\Subscriber\EventSubscriber;

class DeleteQueryEvent extends QueryEvent
{
    public function handle(): int
    {
        $subscriber = current($this->subscribers);
        next($this->subscribers);
        if ($subscriber instanceof EventSubscriber) {
            return $subscriber->onDelete($this);
        }
        return (clone $this->query)->scopeRaw()->delete();
    }

    public function stopPropagation(): int
    {
        return 0;
    }
}
