<?php

namespace Efabrica\NetteRepository\Event;

class DeleteQueryEvent extends QueryEvent
{
    public function handle(): int
    {
        while ($subscriber = current($this->subscribers)) {
            next($this->subscribers);
            if ($subscriber->supportsEvent($this)) {
                return $subscriber->onDelete($this);
            }
        }
        return (clone $this->query)->scopeRaw()->delete();
    }

    public function stopPropagation(): int
    {
        return 0;
    }
}
