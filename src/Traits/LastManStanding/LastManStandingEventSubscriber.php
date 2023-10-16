<?php

namespace Efabrica\NetteRepository\Traits\LastManStanding;

use Efabrica\NetteRepository\Event\DeleteQueryEvent;
use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteSubscriber;
use LogicException;

final class LastManStandingEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    public function supportsEvent(RepositoryEvent $event): bool
    {
        return $event->hasBehavior(LastManStandingBehavior::class);
    }

    private function ensureLastMan(RepositoryEvent $event): void
    {
        $behavior = $event->getBehavior(LastManStandingBehavior::class);
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
