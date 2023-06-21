<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Owner;

use Efabrica\IrisClient\IrisUser;
use Efabrica\NetteDatabaseRepository\Model\EntityMeta;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertEventResponse;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;

class OwnerEventSubscriber extends EventSubscriber
{
    public const CREATED_BY = '@CreatedBy';
    public const UPDATED_BY = '@UpdatedBy';

    private IrisUser $irisUser;

    public function __construct(IrisUser $irisUser)
    {
        $this->irisUser = $irisUser;
    }

    public function supportsRepository(Repository $repository): bool
    {
        return EntityMeta::getAnnotatedProperty($repository->getEntityClass(), self::CREATED_BY) !== null ||
            EntityMeta::getAnnotatedProperty($repository->getEntityClass(), self::UPDATED_BY) !== null;
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        $createdBy = EntityMeta::getAnnotatedProperty($event->getEntityClass(), self::CREATED_BY);
        if ($createdBy !== null) {
            foreach ($event->getEntities() as $entity) {
                if (!isset($entity[$createdBy->getName()])) {
                    $entity[$createdBy->getName()] = $this->irisUser->getId();
                }
            }
        }
        $updatedBy = EntityMeta::getAnnotatedProperty($event->getEntityClass(), self::UPDATED_BY);
        if ($updatedBy !== null) {
            foreach ($event->getEntities() as $entity) {
                if (!isset($entity[$updatedBy->getName()])) {
                    $entity[$updatedBy->getName()] = $this->irisUser->getId();
                }
            }
        }
        return $event->handle();
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        $updatedBy = EntityMeta::getAnnotatedProperty($event->getRepository()->getEntityClass(), self::UPDATED_BY);
        if ($updatedBy !== null) {
            $data[$updatedBy->getName()] = $this->irisUser->getId();
        }
        return $event->handle($data);
    }
}
