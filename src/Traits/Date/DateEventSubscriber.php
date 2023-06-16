<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Date;

use DateTimeImmutable;
use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Subscriber\AnnotationReader;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertEntityEventResponse;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\LoadRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\LoadEntityResponse;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteSubscriber;

class DateEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    public const CREATED_AT = '@CreatedAt';
    public const UPDATED_AT = '@UpdatedAt';

    private AnnotationReader $annotationReader;

    public function __construct(AnnotationReader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    /**
     * @param string $entityClass
     * @param array  $entities
     * @return void
     */
    public function loadEntities(string $entityClass, array $entities): void
    {
        $prop = $this->annotationReader->findProperty($entityClass, self::CREATED_AT);
        if ($prop !== null) {
            foreach ($entities as $entity) {
                if (!$prop->isInitialized($entity)) {
                    $prop->setValue($entity, new DateTimeImmutable());
                }
            }
        }
        $prop = $this->annotationReader->findProperty($entityClass, self::UPDATED_AT);
        if ($prop !== null && !$prop->isInitialized($entity)) {
            foreach ($entities as $entity) {
                if (!$prop->isInitialized($entity)) {
                    $prop->setValue($entity, new DateTimeImmutable());
                }
            }
        }
    }

    public function onLoad(LoadRepositoryEvent $event): LoadEntityResponse
    {
        $this->loadEntities($event->getEntityClass(), [$event->getEntity()]);
        return $event->handle();
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEntityEventResponse
    {
        $this->loadEntities($event->getEntityClass(), $event->getEntities());
        return $event->handle();
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        $prop = $this->annotationReader->findProperty($event->getEntityClass(), self::UPDATED_AT);
        if ($prop !== null) {
            $data[$prop->getName()] = new DateTimeImmutable();
        }
        return $event->handle($data);
    }

    public function softDelete(SoftDeleteQueryEvent $event, array &$data): int
    {
        $prop = $this->annotationReader->findProperty($event->getEntityClass(), self::UPDATED_AT);
        if ($prop !== null) {
            $data[$prop->getName()] = new DateTimeImmutable();
        }
        return $event->handle($data);
    }
}
