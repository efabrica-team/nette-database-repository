<?php

namespace Efabrica\NetteDatabaseRepository\Subscriber\Event;

use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;

class UpdateQueryEvent extends QueryEvent
{
    public function handle(array &$data): int
    {
        $subscriber = current($this->subscribers);
        next($this->subscribers);
        if ($subscriber instanceof EventSubscriber) {
            return $subscriber->onUpdate($this, $data);
        }
        return $this->query->withoutEvents()->update($data);
    }

    public function stopPropagation(): int
    {
        return 0;
    }
}
