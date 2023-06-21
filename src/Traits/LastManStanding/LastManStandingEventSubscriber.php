<?php

namespace Efabrica\NetteDatabaseRepository\Traits\LastManStanding;

use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\DeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteSubscriber;
use LogicException;

class LastManStandingEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    public function supportsRepository(Repository $repository): bool
    {
        return $repository instanceof LastManStandingRepository;
    }

    private function ensureLastMan(Repository $repository): void
    {
        if (!$repository instanceof LastManStandingRepository) {
            throw new LogicException('Repository must implement LastManStandingRepository');
        }
        if ($repository->lastManQuery()->count('*') <= 1) {
            throw new LogicException('At least one record must exist in table');
        }
    }

    public function onDelete(DeleteQueryEvent $event): int
    {
        $this->ensureLastMan($event->getRepository());
        return $event->handle();
    }

    public function onSoftDelete(SoftDeleteQueryEvent $event, array &$data): int
    {
        $this->ensureLastMan($event->getRepository());
        return $event->handle($data);
    }
}
