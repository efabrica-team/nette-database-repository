<?php

namespace Efabrica\NetteDatabaseRepository\Traits\KeepDefault;

use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\AnnotationReader;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\DeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertEntityEventResponse;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteSubscriber;
use LogicException;

class KeepDefaultEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    private AnnotationReader $annotationReader;

    public function __construct(AnnotationReader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    public function supportsRepository(Repository $repository): bool
    {
        return $repository instanceof KeepDefaultRepository;
    }

    private function ensureDefault(Repository $repository): void
        /** @var Repository&KeepDefaultRepository $defaultField */
    {
        $defaultField = $this->annotationReader->findProperty($repository->getEntityClass(), KeepDefaultRepository::ANNOTATION);
        if ($defaultField === null) {
            throw new LogicException('@KeepDefault annotation not found');
        }

        /** @var KeepDefaultRepository&Repository $repository */
        $countQuery = $repository->keepDefaultQuery()->where([$defaultField->getName() => true]);
        $count = $countQuery->count('*');
        if ($count === 1) {
            return;
        }
        if ($count === 0) {
            $entity = $repository->keepDefaultQuery()->limit(1)->fetch();
            if ($entity) {
                $entity[$defaultField->getName()] = true;
                $repository->update($entity);
            }
        }
        // skip first record:
        $countQuery->fetch();
        // set all other records to false:
        while ($entity = $countQuery->fetch()) {
            $entity[$defaultField->getName()] = false;
            $repository->update($entity);
        }
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEntityEventResponse
    {
        /** @var Repository&KeepDefaultRepository $repository */
        $repository = $event->getRepository();
        $result = $event->handle();
        $this->ensureDefault($repository);
        return $result;
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        $result = $event->handle($data);
        /** @var Repository&KeepDefaultRepository $repository */
        $repository = $event->getRepository();
        $defaultField = $this->annotationReader->findProperty($repository->getEntityClass(), KeepDefaultRepository::ANNOTATION);
        if ($defaultField === null) {
            throw new LogicException('@KeepDefault annotation not found');
        }
        if (!isset($data[$defaultField->getName()])) {
            return $result;
        }
        $this->ensureDefault($repository);
        return $result;
    }

    public function onDelete(DeleteQueryEvent $event): int
    {
        $result = $event->handle();
        $this->ensureDefault($event->getRepository());
        return $result;
    }

    public function softDelete(SoftDeleteQueryEvent $event, array &$data): int
    {
        $result = $event->handle($data);
        $this->ensureDefault($event->getRepository());
        return $result;
    }
}
