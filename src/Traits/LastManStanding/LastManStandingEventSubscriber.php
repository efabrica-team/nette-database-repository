<?php

namespace Efabrica\NetteDatabaseRepository\Traits\LastManStanding;

use Efabrica\NetteDatabaseRepository\Event\DeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Event\RepositoryEvent;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteSubscriber;
use LogicException;

class LastManStandingEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    public function supportsRepository(Repository $repository): bool
    {
        return $repository->behaviors()->has(LastManStandingBehavior::class);
    }

    private function ensureLastMan(RepositoryEvent $event): void
    {
        /** @var LastManStandingBehavior $behavior */
        $behavior = $event->getBehaviors()->get(LastManStandingBehavior::class);
        if ($behavior->getQuery()->count('*') <= 1) {
            throw new LogicException('At least one record must exist in table');
        }
    }

    public function onDelete(DeleteQueryEvent $event): int
    {
        $this->ensureLastMan($event);
        return $event->handle();
    }

    public function onSoftDelete(SoftDeleteQueryEvent $event, array &$data): int
    {
        $this->ensureLastMan($event);
        return $event->handle($data);
    }
}
