<?php

namespace Efabrica\NetteDatabaseRepository\Event;

use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Repository\Query;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Repository\RepositoryBehaviors;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;

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
        if ($this->subscribers === []) {
            $this->subscribers = $repository->getEvents()->toArray();
        }
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
