<?php

namespace Efabrica\NetteRepository\Traits\Sorting;

use Efabrica\NetteRepository\Event\InsertEventResponse;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;
use Efabrica\NetteRepository\Traits\DefaultOrder\DefaultOrderBehavior;
use Efabrica\NetteRepository\Traits\RelatedThrough\GetRelatedThroughQueryEvent;
use Efabrica\NetteRepository\Traits\RelatedThrough\RelatedThroughEventSubscriber;
use Efabrica\NetteRepository\Traits\RelatedThrough\SetRelatedThroughRepositoryEvent;

class SortingEventSubscriber extends EventSubscriber implements RelatedThroughEventSubscriber
{
    public function supportsEvent(RepositoryEvent $event): bool
    {
        return $event->hasBehavior(SortingBehavior::class);
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        $behavior = $event->getBehavior(SortingBehavior::class);
        $sortingColumn = $behavior->getColumn();
        $max = $event->getRepository()->query()->max($sortingColumn);
        foreach ($event->getEntities() as $entity) {
            if (!isset($entity->$sortingColumn)) {
                $max += $behavior->getStep();
                $entity->$sortingColumn = $max;
            }
        }
        return $event->handle();
    }

    public function onGetRelated(GetRelatedThroughQueryEvent $event): Query
    {
        $behavior = $event->getBehavior(SortingBehavior::class);
        $event->getQuery()->getBehaviors()->add(new DefaultOrderBehavior($behavior->getColumn(), $behavior->getDirection()));
        return $event->handle();
    }

    public function onSetRelated(SetRelatedThroughRepositoryEvent $event): int
    {
        $result = $event->handle();
        $behavior = $event->getBehavior(SortingBehavior::class);
        $currentSort = $event->getRepository()->findBy([$event->getOwnerColumn() => $event->getOwner()->getPrimary()])
            ->fetchPairs($event->getOwnedColumn(), $behavior->getColumn());
        foreach ($event->getOwnedIds() as $i => $id) {
            if ($currentSort[$id] !== $i * $behavior->getStep()) {
                $event->getRepository()
                    ->findBy([$event->getOwnerColumn() => $event->getOwner()->getPrimary(), $event->getOwnedColumn() => $id])
                    ->update([$behavior->getColumn() => $i * $behavior->getStep()]);
            }
        }
        return $result;
    }
}
