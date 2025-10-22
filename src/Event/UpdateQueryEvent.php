<?php

namespace Efabrica\NetteRepository\Event;

use Efabrica\NetteRepository\Repository\Entity;
use Efabrica\NetteRepository\Repository\QueryInterface;
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
        $this->refreshEntities($data, $rawQuery);
        return new UpdateEventResponse($this, $update);
    }

    private function refreshEntities(array $data, QueryInterface $rawQuery): void
    {
        $entities = $this->getEntities();
        if (!is_array($entities) || $this->getQuery()->getPrimary(false) === null) {
            return;
        }

        $newEntities = $this->fetchNewEntities($data, $entities, $rawQuery);
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
     * @param array          $updateData
     * @param array          $entities
     * @param QueryInterface $rawQuery
     * @return Entity[]
     */
    private function fetchNewEntities(array $updateData, array $entities, QueryInterface $rawQuery): array
    {
        $primaryKeys = $this->getRepository()->getPrimary();
        $newEntities = [];
        if (array_intersect_key($updateData, array_flip($primaryKeys)) !== []) {
            $newEntityQuery = $this->getRepository()->query()->whereEntities($entities, false);
        } else {
            $newEntityQuery = $rawQuery->where('1=1')->fetchAll();
        }
        /** @var Entity $entity */
        foreach ($newEntityQuery as $entity) {
            $newEntities[$entity->getSignature()] = $entity;
        }
        return $newEntities;
    }
}
