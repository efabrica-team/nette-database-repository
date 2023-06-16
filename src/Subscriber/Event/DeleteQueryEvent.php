<?php

namespace Efabrica\NetteDatabaseRepository\Subscriber\Event;

use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;

class DeleteQueryEvent extends QueryEvent
{
    public function handle(): int
    {
        $subscriber = current($this->subscribers);
        next($this->subscribers);
        if ($subscriber instanceof EventSubscriber) {
            return $subscriber->onDelete($this);
        }
        return $this->query->withoutEvents()->delete();
    }

    public function stopPropagation(): int
    {
        return 0;
    }
}
