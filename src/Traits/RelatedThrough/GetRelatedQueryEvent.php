<?php

namespace Efabrica\NetteRepository\Traits\RelatedThrough;

use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Repository\Entity;
use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;
use IteratorIterator;
use LogicException;

class GetRelatedQueryEvent extends RepositoryEvent
{
    public const PIVOT = 'pivot';

    private readonly Query $query;

    private readonly Repository $throughRepo;

    private readonly Entity $owner;

    private readonly string $ownerColumn;

    private readonly string $ownedColumn;

    public function __construct(Entity $owner, Repository $throughRepo, Repository $otherRepo, string $ownerColumn, string $ownedColumn)
    {
        $otherPrimary = $otherRepo->getPrimary();
        if (count($otherPrimary) !== 1) {
            throw new LogicException('Related entity ' . $otherRepo->getEntityClass() . ' has none or more than one primary column');
        }
        parent::__construct($throughRepo);
        $this->query = $otherRepo->query()->alias(':' . $throughRepo->getTableName(), self::PIVOT)->where([
            self::PIVOT . ".$ownerColumn" => $owner->getPrimary(),
            self::PIVOT . ".$ownedColumn = {$otherRepo->getTableName()}.{$otherPrimary[0]}",
        ]);
        $this->throughRepo = $throughRepo;
        $this->owner = $owner;
        $this->ownerColumn = $ownerColumn;
        $this->ownedColumn = $ownedColumn;
    }

    public function handle(): GetRelatedEventResponse
    {
        while ($subscriber = current($this->subscribers)) {
            /** @var EventSubscriber $subscriber */
            next($this->subscribers);
            if ($subscriber instanceof RelatedEventSubscriber && $subscriber->supportsEvent($this)) {
                return $subscriber->onGetRelated($this);
            }
        }
        return $this->stopPropagation();
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    public function getEntities(): iterable
    {
        return new IteratorIterator($this->query);
    }

    public function getThroughRepo(): Repository
    {
        return $this->throughRepo;
    }

    public function getOwner(): Entity
    {
        return $this->owner;
    }

    public function getOwnerColumn(): string
    {
        return $this->ownerColumn;
    }

    public function getOwnedColumn(): string
    {
        return $this->ownedColumn;
    }

    public function stopPropagation(): GetRelatedEventResponse
    {
        assert($this->query instanceof Query);
        $this->ended = true;
        return new GetRelatedEventResponse($this, $this->query);
    }
}
