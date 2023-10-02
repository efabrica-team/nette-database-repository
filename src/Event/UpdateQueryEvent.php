<?php

namespace Efabrica\NetteRepository\Event;

class UpdateQueryEvent extends QueryEvent
{
    public function handle(array &$data): int
    {
        while ($subscriber = current($this->subscribers)) {
            next($this->subscribers);
            if ($subscriber->supportsEvent($this)) {
                return $subscriber->onUpdate($this, $data);
            }
        }
        foreach ($this->getEntities() as $entity) {
            $entity->internalData($data);
        }
        return $this->query->scopeRaw()->update($data);
    }

    public function stopPropagation(): int
    {
        return 0;
    }
}
