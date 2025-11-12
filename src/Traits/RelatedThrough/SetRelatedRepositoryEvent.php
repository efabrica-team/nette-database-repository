<?php

namespace Efabrica\NetteRepository\Traits\RelatedThrough;

use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Event\RepositoryEventResponse;
use Efabrica\NetteRepository\Repository\Entity;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;
use IteratorIterator;
use LogicException;

class SetRelatedRepositoryEvent extends RepositoryEvent
{
    private readonly Repository $throughRepo;

    /** @var (string|int)[] */
    private readonly array $ownedIds;

    /**
     * @param Repository $throughRepo Many to many repository
     * @param Entity $owner Owner entity (ex.: Group)
     * @param iterable<Entity|int|string> $owned Entities or IDs that should be related to the owner (ex.: User[])
     * @param string $ownerColumn Column in the through table that references the owner (ex.: "group_id")
     * @param string $ownedColumn Column in the through table that references the owned (ex.: "user_id")
     */
    public function __construct(Repository $throughRepo, private Entity $owner, private readonly iterable $owned, private readonly string $ownerColumn, private readonly string $ownedColumn)
    {
        parent::__construct($throughRepo);
        $this->throughRepo = $throughRepo;
        $this->ownedIds = $this->mapOwnedToIds($this->owned);
    }

    public function handle(): SetRelatedEventResponse
    {
        while ($subscriber = current($this->subscribers)) {
            /** @var EventSubscriber $subscriber */
            next($this->subscribers);
            if ($subscriber instanceof RelatedEventSubscriber && $subscriber->supportsEvent($this)) {
                return $subscriber->onSetRelated($this);
            }
        }
        $this->ended = true;
        return new SetRelatedEventResponse($this, $this->execute());
    }

    public function mapOwnedToIds(iterable $owned): array
    {
        $ownedIds = [];
        foreach ($owned as $ownedRow) {
            if ($ownedRow instanceof Entity) {
                $ownedId = $ownedRow->getPrimary();
            } elseif (is_int($ownedRow) || is_string($ownedRow)) {
                $ownedId = $ownedRow;
            } else {
                throw new LogicException('Invalid owned entity');
            }
            assert(is_int($ownedId) || is_string($ownedId));
            $ownedIds[] = $ownedId;
        }
        return $ownedIds;
    }

    private function execute(): int
    {
        $ownerId = $this->owner->getPrimary();
        $existingIds = $this->throughRepo->query()
            ->select($this->ownedColumn)
            ->where([$this->ownerColumn => $ownerId])
            ->fetchPairs($this->ownedColumn, $this->ownedColumn)
        ;

        $idsToDelete = array_diff($existingIds, $this->ownedIds);
        if ($idsToDelete !== []) {
            $this->throughRepo->findBy([
                $this->ownerColumn => $ownerId,
                $this->ownedColumn => $idsToDelete,
            ])->delete();
        }
        $idsToInsert = array_diff($this->ownedIds, $existingIds);
        if ($idsToInsert !== []) {
            $this->throughRepo->insert(
                array_map(fn($idToInsert) => [
                    $this->ownerColumn => $ownerId,
                    $this->ownedColumn => $idToInsert,
                ], array_values($idsToInsert))
            );
        }
        return count($idsToInsert) + count($idsToDelete);
    }

    public function getThroughRepo(): Repository
    {
        return $this->throughRepo;
    }

    public function getOwner(): Entity
    {
        return $this->owner;
    }

    public function getOwned(): iterable
    {
        return $this->owned;
    }

    public function getEntities(): iterable
    {
        return new IteratorIterator($this->throughRepo->findBy([
            $this->ownerColumn => $this->owner[$this->ownerColumn],
        ]));
    }

    /**
     * @return (string|int)[]
     */
    public function getOwnedIds(): array
    {
        return $this->ownedIds;
    }

    public function getOwnerColumn(): string
    {
        return $this->ownerColumn;
    }

    public function getOwnedColumn(): string
    {
        return $this->ownedColumn;
    }

    public function stopPropagation(): RepositoryEventResponse
    {
        $this->ended = true;
        return new SetRelatedEventResponse($this, 0);
    }
}
