<?php

namespace Efabrica\NetteRepository\Event;

use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Repository\RepositoryBehaviors;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;

/**
 * @template E of Entity
 * @template R *EntityEventResponse
 */
abstract class RepositoryEvent
{
    /**
     * @var EventSubscriber[]
     */
    protected array $subscribers = [];

    /**
     * @var Repository<E, Query<E>>
     */
    private Repository $repository;

    /**
     * @param Repository<E, Query<E>> $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->subscribers = $repository->getEventSubscribers()->toArray();
    }

    public function hasEnded(): bool
    {
        return $this->subscribers === [];
    }

    /**
     * @return class-string<E>
     */
    public function getEntityClass(): string
    {
        return $this->repository->getEntityClass();
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    public function getBehaviors(): RepositoryBehaviors
    {
        return $this->repository->behaviors();
    }

    /**
     * Stop the execution of the event chain.
     * @return mixed
     */
    abstract public function stopPropagation();
}
