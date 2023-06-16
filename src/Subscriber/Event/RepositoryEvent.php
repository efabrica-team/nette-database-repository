<?php

namespace Efabrica\NetteDatabaseRepository\Subscriber\Event;

use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Repository\Query;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
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
    protected array $subscribers;

    private Repository $repository;

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

    /**
     * Stop the execution of the event chain.
     */
    abstract public function stopPropagation();
}
