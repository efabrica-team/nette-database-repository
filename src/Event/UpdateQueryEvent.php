<?php

namespace Efabrica\NetteRepository\Event;

use Efabrica\NetteRepository\Repository\Entity;
use Generator;
use LogicException;
use SplObjectStorage;

class UpdateQueryEvent extends QueryEvent
{
    /**
     * @var SplObjectStorage<Entity, array>
     */
    private ?SplObjectStorage $diff = null;

    public function handle(array &$data): UpdateEventResponse
    {
        while ($subscriber = current($this->subscribers)) {
            next($this->subscribers);
            if ($subscriber->supportsEvent($this)) {
                return $subscriber->onUpdate($this, $data);
            }
        }
        $this->ended = true;

        $this->diff = new SplObjectStorage();
        foreach ($this->getEntities() as $entity) {
            $this->diff->attach($entity, $entity->toOriginalArray());
        }
        $rawQuery = $this->query->scopeRaw();
        $update = $rawQuery->update($data);
        $this->refreshEntities($data);
        return new UpdateEventResponse($this, $update);
    }

    private function refreshEntities(array $data): void
    {
        $entities = $this->getEntities();
        if (!is_array($entities) || $this->getQuery()->getPrimary(false) === null) {
            return;
        }

        $newEntities = $this->fetchNewEntities($data, $entities);
        foreach ($entities as $entity) {
            $signature = $entity->getSignature(true, false);
            if ($signature !== '') {
                if (!isset($newEntities[$signature])) {
                    throw new LogicException('Entity was not found after update. This is internal error of the library. Please report it.');
                }
                $entity->setInternalData($newEntities[$signature]->toArray());
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
            $oldEntity = $repository->createRow($this->diff[$entity]);
            yield $oldEntity => $entity;
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

    public function stopPropagation(): UpdateEventResponse
    {
        $this->ended = true;
        return new UpdateEventResponse($this, 0);
    }

    /**
     * @param array $updateData
     * @param array $entities
     * @return Entity[]
     */
    private function fetchNewEntities(array $updateData, array $entities): array
    {
        $primaryKeys = $this->getRepository()->getPrimary();
        $newEntities = [];
        // Reload the just-updated rows purely by primary key, on a fresh, raw-scoped query:
        //  - fresh, because the original query's WHERE may reference columns this update just changed,
        //    which would make the refetch return nothing;
        //  - raw-scoped, because behavior filters (e.g. SoftDelete's "deleted_at IS NULL") could likewise
        //    exclude a row we just wrote. onLoad transforms such as Cast still run, since they are gated by
        //    the repository's behaviors, not by the query scope.
        // If the update changed a primary key, look the rows up by their new (already-filled in-memory) key
        // values; otherwise by their original key values (see #Entity was not found after update).
        $useCurrent = array_intersect_key($updateData, array_flip($primaryKeys)) !== [];
        $newEntityQuery = $this->getRepository()->query()->scopeRaw()->whereEntities($entities, !$useCurrent);
        /** @var Entity $entity */
        foreach ($newEntityQuery as $entity) {
            $newEntities[$entity->getSignature()] = $entity;
        }
        return $newEntities;
    }
}
