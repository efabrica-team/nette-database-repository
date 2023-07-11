<?php

namespace Efabrica\NetteDatabaseRepository\Traits\AutoAlias;

use Efabrica\NetteDatabaseRepository\Event\InsertEventResponse;
use Efabrica\NetteDatabaseRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Repository\RepositoryManager;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;

class AutoAliasEventSubscriber extends EventSubscriber
{
    public function supportsRepository(Repository $repository): bool
    {
        return RepositoryManager::hasTrait($repository, AutoAliasBehavior::class);
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        /** @var AutoAliasRepository $repository */
        $repository = $event->getRepository();
        foreach ($event->getEntities() as $entity) {
            $repository->setEntityAlias($entity);
        }
        return $event->handle();
    }
}
