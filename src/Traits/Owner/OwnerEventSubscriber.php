<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Owner;

use Efabrica\IrisClient\IrisUser;
use Efabrica\NetteDatabaseRepository\Event\InsertEventResponse;
use Efabrica\NetteDatabaseRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;

class OwnerEventSubscriber extends EventSubscriber
{
    private IrisUser $irisUser;

    public function __construct(IrisUser $irisUser)
    {
        $this->irisUser = $irisUser;
    }

    public function supportsRepository(Repository $repository): bool
    {
        return $repository->behaviors()->has(OwnerBehavior::class);
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        /** @var OwnerBehavior $behavior */
        $behavior = $event->getBehaviors()->get(OwnerBehavior::class);
        $createdBy = $behavior->getCreatedBy();
        if ($createdBy !== null) {
            foreach ($event->getEntities() as $entity) {
                if (!isset($entity[$createdBy])) {
                    $entity[$createdBy] = $this->irisUser->getId();
                }
            }
        }
        $updatedBy = $behavior->getUpdatedBy();
        if ($updatedBy !== null) {
            foreach ($event->getEntities() as $entity) {
                if (!isset($entity[$updatedBy])) {
                    $entity[$updatedBy] = $this->irisUser->getId();
                }
            }
        }
        return $event->handle();
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        /** @var OwnerBehavior $behavior */
        $behavior = $event->getBehaviors()->get(OwnerBehavior::class);
        $updatedBy = $behavior->getUpdatedBy();
        if ($updatedBy !== null && !isset($data[$updatedBy])) {
            $data[$updatedBy] = $this->irisUser->getId();
        }
        return $event->handle($data);
    }
}
