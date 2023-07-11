<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Cast;

use Efabrica\NetteDatabaseRepository\Event\InsertEventResponse;
use Efabrica\NetteDatabaseRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Model\EntityMeta;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;

abstract class CastEventSubscriber extends EventSubscriber
{
    public function supportsRepository(Repository $repository): bool
    {
        return EntityMeta::getAnnotatedProperty($repository->getEntityClass(), $this->getAnnotation()) !== null;
    }

    abstract protected function getAnnotation(): string;

    /**
     * @param mixed $from value from database
     * @return mixed value for entity
     * @example JSON decode
     */
    abstract protected function castTo($from);

    /**
     * @param mixed $to value in entity
     * @return mixed value for database
     * @example JSON encode
     */
    abstract protected function castFrom($to);

    public function onCreate(Entity $entity): void
    {
        foreach (EntityMeta::getAnnotatedProperties(get_class($entity), $this->getAnnotation()) as $prop) {
            $propName = $prop->getName();
            if (isset($entity[$propName])) {
                $entity[$propName] = $this->castTo($entity[$propName]);
            }
        }
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        foreach (EntityMeta::getAnnotatedProperties($event->getEntityClass(), $this->getAnnotation()) as $prop) {
            $propName = $prop->getName();
            foreach ($event->getEntities() as $entity) {
                $entity[$propName] = $this->castFrom($entity[$propName]);
            }
        }
        return $event->handle();
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        foreach (EntityMeta::getAnnotatedProperties($event->getEntityClass(), $this->getAnnotation()) as $prop) {
            $propName = $prop->getName();
            if (array_key_exists($propName, $data)) {
                $data[$propName] = $this->castFrom($data[$propName]);
            }
        }
        return $event->handle($data);
    }
}
