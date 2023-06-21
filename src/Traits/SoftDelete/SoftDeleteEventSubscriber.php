<?php

namespace Efabrica\NetteDatabaseRepository\Traits\SoftDelete;

use DateTimeImmutable;
use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Model\EntityMeta;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\DeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\SelectQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\SelectQueryResponse;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;
use LogicException;

class SoftDeleteEventSubscriber extends EventSubscriber
{
    public const ANNOTATION = '@SoftDelete';

    public function supportsRepository(Repository $repository): bool
    {
        return EntityMeta::getAnnotatedProperty($repository->getEntityClass(), self::ANNOTATION) !== null;
    }

    public function onSelect(SelectQueryEvent $event): SelectQueryResponse
    {
        $prop = EntityMeta::getAnnotatedProperty($event->getRepository()->getEntityClass(), self::ANNOTATION);
        if ($prop !== null) {
            $event->getQuery()->where($event->getRepository()->getTableName() . '.' . $prop->getName() . ' = NULL', false);
        }
        return $event->handle();
    }

    public function onDelete(DeleteQueryEvent $event): int
    {
        $prop = EntityMeta::getAnnotatedProperty($event->getRepository()->getEntityClass(), self::ANNOTATION);
        if ($prop === null) {
            return $event->handle();
        }
        $data = [];
        if ($prop->getType() === 'bool') {
            $data[$prop->getName()] = true;
        } elseif ($prop->getType() === 'DateTimeInterface') {
            $data[$prop->getName()] = new DateTimeImmutable();
        } else {
            throw new LogicException('Unsupported soft delete property type ' . $prop->getType() . '. Supported types are bool and DateTimeInterface.');
        }
        return (new SoftDeleteQueryEvent($event->getQuery()))->handle($data);
    }

    public function restore(Repository $repository, Entity $entity): int
    {
        $prop = EntityMeta::getAnnotatedProperty($repository->getEntityClass(), self::ANNOTATION);
        if ($prop === null) {
            throw new LogicException('Cannot restore entity without soft delete property.');
        }
        if ($prop->getType() === 'bool') {
            $entity[$prop->getName()] = false;
        } elseif ($prop->getType() === 'DateTimeInterface') {
            $entity[$prop->getName()] = null;
        } else {
            throw new LogicException('Unsupported soft delete property type ' . $prop->getType() . '. Supported types are bool and DateTimeInterface.');
        }
        return $repository->update($entity);
    }
}
