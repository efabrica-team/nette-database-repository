<?php

namespace Efabrica\NetteRepository\Event;

use Efabrica\NetteRepository\Repository\Entity;
use Efabrica\NetteRepository\Repository\QueryInterface;
use Generator;
use LogicException;
use SplObjectStorage;

class UpdateQueryEvent extends QueryEvent
{
    private ?SplObjectStorage $diff = null;

    public function handle(array &$data): int
    {
        while ($subscriber = current($this->subscribers)) {
            next($this->subscribers);
            if ($subscriber->supportsEvent($this)) {
                return $subscriber->onUpdate($this, $data);
            }
        }

        $this->diff = new SplObjectStorage();
        foreach ($this->getEntities() as $entity) {
            $this->diff->attach($entity, $entity->toOriginalArray());
        }
        $rawQuery = $this->query->scopeRaw();
        $update = $rawQuery->update($data);
        $this->updateInternalData($rawQuery);
        return $update;
    }

    private function updateInternalData(QueryInterface $rawQuery): void
    {
        if ($rawQuery->getPrimary(false) === null) {
            return;
        }
        $whereRows = $rawQuery->getWhereRows();
        if ($whereRows === []) {
            $whereRows = $this->getEntities();
        }
        foreach ($rawQuery->where('1=1')->fetchAll() as $newRow) {
            foreach ($whereRows as $entity) {
                if (!$entity instanceof Entity) {
                    continue;
                }
                if ($entity->getPrimary() === $newRow->getPrimary()) {
                    $entity->internalData($newRow->toArray(), false);
                    break;
                }
            }
        }
    }

    /**
     * @return Generator<Entity, Entity> foreach ($event->getDiff() as $oldCopyOfEntity => $currentUpdatedEntity)
     * $oldCopyOfEntity is a copy of the entity as it was before the update (it's not scalar, it's Entity even though it's key)
     * $currentUpdatedEntity is the entity as it is now, the same entity you sent to the update method.
     */
    public function getDiff(): Generator
    {
        if ($this->diff === null) {
            throw new LogicException('Diff is not available before the event is handled');
        }
        $repository = $this->getRepository();
        foreach ($this->diff as $entity) {
            yield $repository->createRow($this->diff[$entity]) => $entity;
        }
    }

    public function computeDiff(Entity $oldEntity, Entity $newEntity): array
    {
        $diff = [];
        foreach ($oldEntity->toArray() as $key => $value) {
            if ($newEntity::isSameValue($value, $newEntity->$key ?? null)) {
                $diff[$key] = $newEntity->$key ?? null;
            }
        }
        return $diff;
    }

    public function stopPropagation(): int
    {
        return 0;
    }
}
