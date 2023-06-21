<?php

namespace Efabrica\NetteDatabaseRepository\Repository;

use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\DeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\SelectQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Events;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;
use Nette\Database\Table\Selection;
use Traversable;

/**
 * @template E of Entity
 */
class Query extends Selection
{
    private bool $doesEvents;

    private Repository $repository;

    private Events $events;

    /**
     * @param Repository<E,$this> $repository
     * @param bool       $events
     */
    public function __construct(Repository $repository, bool $events = true)
    {
        $this->repository = $repository;
        $this->doesEvents = $events;
        $this->events = clone $repository->getEvents();
        parent::__construct($repository->getExplorer(), $repository->getExplorer()->getConventions(), $repository->getTableName());
    }

    /************************** Modifications *****************************/

    /**
     * @param E[]|E $data
     * @return bool|int|E
     */
    public function insert(iterable $data)
    {
        if (!$this->doesEvents()) {
            /** @var bool|int|E $return */
            $return = parent::insert($data);
            return $return;
        }
        if ($data instanceof Entity) {
            $data = [$data];
        }
        return (new InsertRepositoryEvent($this->repository, $data))->handle()->getReturn();
    }

    public function update(iterable $data): int
    {
        if (!$this->doesEvents()) {
            return parent::update($data);
        }
        $data = $data instanceof Traversable ? iterator_to_array($data) : $data;
        return (new UpdateQueryEvent($this))->handle($data);
    }

    public function delete(): int
    {
        if (!$this->doesEvents()) {
            return parent::delete();
        }
        return (new DeleteQueryEvent($this))->handle();
    }

    protected function execute(): void
    {
        if ($this->rows === null && $this->doesEvents()) {
            (new SelectQueryEvent($this))->handle();
        }
        parent::execute();
    }

    /********************************** Events ***************************/

    /**
     * @param class-string<EventSubscriber> ...$eventClasses
     * @return Query cloned instance
     */
    public function withoutEvent(string ...$eventClasses): Query
    {
        $clone = clone $this;
        foreach ($eventClasses as $eventClass) {
            $clone->events->removeEvent($eventClass);
        }
        return $clone;
    }

    public function withoutEvents(): Query
    {
        $clone = clone $this;
        $clone->doesEvents = false;
        return $clone;
    }

    /********************************** Getters **************************/
    public function getRepository(): Repository
    {
        return $this->repository;
    }

    public function getEvents(): Events
    {
        return $this->events;
    }

    public function doesEvents(): bool
    {
        return $this->doesEvents;
    }

    /************************* Types **************************/
    /* This section is here to help IDEs with type inference */
    protected function createRow(array $row): Entity
    {
        $entityClass = $this->repository->getEntityClass();
        return new $entityClass($row, $this);
    }

    public function createSelectionInstance(?string $table = null): Query
    {
        return new Query($this->repository, $this->doesEvents);
    }

    /**
     * @param mixed|Entity $condition
     * @param mixed ...$params
     * @return $this
     */
    public function where($condition, ...$params): self
    {
        if ($condition instanceof Entity) {
            return $this->wherePrimary($condition->getPrimary());
        }
        parent::where($condition, $params);
        return $this;
    }

    /**
     * @param mixed|Entity $key
     * @return $this
     */
    public function wherePrimary($key): self
    {
        if ($key instanceof Entity) {
            $key = $key->getPrimary();
        }
        parent::wherePrimary($key);
        return $this;
    }

    /**
     * @return E|null
     */
    public function fetch(): ?Entity
    {
        /** @var E|null $entity */
        $entity = parent::fetch();
        return $entity;
    }

    /**
     * @return E[]
     */
    public function fetchAll(): array
    {
        /** @var E[] $rows */
        $rows = parent::fetchAll();
        return $rows;
    }
}
