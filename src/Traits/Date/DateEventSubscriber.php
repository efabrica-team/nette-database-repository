<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Date;

use DateTimeImmutable;
use Efabrica\NetteDatabaseRepository\Event\InsertEventResponse;
use Efabrica\NetteDatabaseRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Model\EntityMeta;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteSubscriber;

class DateEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    public const CREATED_AT = '@CreatedAt';
    public const UPDATED_AT = '@UpdatedAt';

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        $prop = EntityMeta::getAnnotatedProperty($event->getEntityClass(), self::CREATED_AT);
        if ($prop !== null) {
            foreach ($event->getEntities() as $entity) {
                if (!isset($entity[$prop->getName()])) {
                    $entity[$prop->getName()] = new DateTimeImmutable();
                }
            }
        }
        $prop = EntityMeta::getAnnotatedProperty($event->getEntityClass(), self::UPDATED_AT);
        if ($prop !== null) {
            foreach ($event->getEntities() as $entity) {
                if (!isset($entity[$prop->getName()])) {
                    $entity[$prop->getName()] = new DateTimeImmutable();
                }
            }
        }
        return $event->handle();
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        $prop = EntityMeta::getAnnotatedProperty($event->getEntityClass(), self::UPDATED_AT);
        if ($prop !== null) {
            $data[$prop->getName()] = new DateTimeImmutable();
        }
        return $event->handle($data);
    }

    public function onSoftDelete(SoftDeleteQueryEvent $event, array &$data): int
    {
        $prop = EntityMeta::getAnnotatedProperty($event->getEntityClass(), self::UPDATED_AT);
        if ($prop !== null) {
            $data[$prop->getName()] = new DateTimeImmutable();
        }
        return $event->handle($data);
    }
}
