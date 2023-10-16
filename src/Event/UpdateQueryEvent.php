<?php

namespace Efabrica\NetteRepository\Event;

use Efabrica\NetteRepository\Model\Entity;

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
        $updateQuery = $this->query->scopeRaw();
        $update = $updateQuery->update($data);
        foreach ($this->query->getWhereRows() as $row) {
            if ($row instanceof Entity) {
                $updatedEntities ??= $updateQuery->select('*')->fetchAll();
                foreach ($updatedEntities as $entity) {
                    if ($entity->getPrimary() === $row->getPrimary()) {
                        $row->internalData($entity->toArray());
                    }
                }
            }
        }
        return $update;
    }

    public function stopPropagation(): int
    {
        return 0;
    }
}
