<?php

namespace Efabrica\NetteRepository\Traits\ManyToMany;

use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;
use LogicException;

class ManyToManyRepositoryEvent extends RepositoryEvent
{
    private Repository $mnRepo;

    private Entity $owner;

    private iterable $owned;

    /** @var (string|int)[] */
    private array $ownedIds;

    private string $ownerColumn;

    private string $ownedColumn;

    /**
     * @param Repository $mnRepo Many to many repository
     * @param iterable   $owned Entities or IDs
     */
    public function __construct(Repository $mnRepo, Entity $owner, iterable $owned, string $ownerColumn, string $ownedColumn)
    {
        parent::__construct($mnRepo);
        $this->mnRepo = $mnRepo;
        $this->owner = $owner;
        $this->owned = $owned;
        $this->ownedIds = $this->mapOwnedToIds($owned);
        $this->ownerColumn = $ownerColumn;
        $this->ownedColumn = $ownedColumn;
    }

    public function handle(): int
    {
        while ($subscriber = current($this->subscribers)) {
            /** @var EventSubscriber $subscriber */
            next($this->subscribers);
            if ($subscriber instanceof ManyToManyEventSubscriber && $subscriber->supportsEvent($this)) {
                return $subscriber->onManyToMany($this);
            }
        }
        return $this->execute();
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
        $existingIds = $this->mnRepo->query()
            ->select($this->ownedColumn)
            ->where([$this->ownerColumn => $ownerId])
            ->fetchPairs($this->ownedColumn, $this->ownedColumn)
        ;

        $idsToDelete = array_diff($existingIds, $this->ownedIds);
        if ($idsToDelete === []) {
            $this->mnRepo->findBy([
                $this->ownerColumn => $ownerId,
                $this->ownedColumn => $idsToDelete,
            ])->delete();
        }
        $idsToInsert = array_diff($this->ownedIds, $existingIds);
        $this->mnRepo->insert(array_map(fn($idToInsert) => [
            $this->ownerColumn => $ownerId,
            $this->ownedColumn => $idToInsert,
        ], $idsToInsert));

        return count($idsToInsert) + count($idsToDelete);
    }

    public function getMnRepo(): Repository
    {
        return $this->mnRepo;
    }

    public function getOwner(): Entity
    {
        return $this->owner;
    }

    public function getOwned(): iterable
    {
        return $this->owned;
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

    public function stopPropagation(): void
    {
    }
}
