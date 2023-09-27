<?php

namespace Efabrica\NetteRepository\Event;

use Efabrica\NetteRepository\Subscriber\EventSubscriber;

class UpdateQueryEvent extends QueryEvent
{
    public function handle(array &$data): int
    {
        while ($subscriber = current($this->subscribers)) {
            next($this->subscribers);
            if ($subscriber->supportsRepository($this->getRepository())) {
                return $subscriber->onUpdate($this, $data);
            }
        }
        return (clone $this->query)->scopeRaw()->update($data);
    }

    public function stopPropagation(): int
    {
        return 0;
    }
}
