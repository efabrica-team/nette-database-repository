<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Owner;

use Efabrica\IrisClient\IrisUser;
use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\AnnotationReader;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertEntityEventResponse;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\LoadRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\LoadEntityResponse;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;

class OwnerEventSubscriber extends EventSubscriber
{
    public const CREATED_BY = '@CreatedBy';
    public const UPDATED_BY = '@UpdatedBy';

    private AnnotationReader $annotationReader;

    private IrisUser $irisUser;

    public function __construct(AnnotationReader $annotationReader, IrisUser $irisUser)
    {
        $this->annotationReader = $annotationReader;
        $this->irisUser = $irisUser;
    }

    public function supportsRepository(Repository $repository): bool
    {
        return $this->annotationReader->findProperty($repository->getEntityClass(), self::CREATED_BY) !== null ||
            $this->annotationReader->findProperty($repository->getEntityClass(), self::UPDATED_BY) !== null;
    }

    public function onLoad(LoadRepositoryEvent $event): LoadEntityResponse
    {
        $class = $event->getEntityClass();
        $entity = $event->getEntity();
        $createdBy = $this->annotationReader->findProperty($class, self::CREATED_BY);
        if ($createdBy !== null) {
            if (!$createdBy->isInitialized($entity) || $createdBy->getValue($entity) === null) {
                $entity[$createdBy->getName()] = $this->irisUser->getId();
            }
        }
        $updatedBy = $this->annotationReader->findProperty($class, self::UPDATED_BY);
        if ($updatedBy !== null) {
            if (!$updatedBy->isInitialized($entity) || $updatedBy->getValue($entity) === null) {
                $entity[$updatedBy->getName()] = $this->irisUser->getId();
            }
        }
        return $event->handle();
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEntityEventResponse
    {
        $this->onLoad($event);
        return $event->handle();
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        $updatedBy = $this->annotationReader->findProperty($event->getEntityClass(), self::UPDATED_BY);
        if ($updatedBy !== null && !isset($data[$updatedBy->getName()])) {
            $data[$updatedBy->getName()] = $this->irisUser->getId();
        }
        return $event->handle($data);
    }
}
