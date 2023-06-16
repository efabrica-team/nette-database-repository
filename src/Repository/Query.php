<?php

namespace Efabrica\NetteDatabaseRepository\Repository;

use CachingIterator;
use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Subscriber\Events;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;
use Generator;
use Iterator;
use IteratorAggregate;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

/**
 * @template E of Entity
 * @mixin SelectionQuery // all methods of selection should be callable on query
 */
class Query implements IteratorAggregate
{
    private Selection $selection;

    private bool $doesEvents;

    private Repository $repository;

    private Events $events;

    private ?Iterator $innerIterator = null;

    public function __construct(Repository $repository, bool $events = true)
    {
        $this->repository = $repository;
        $this->doesEvents = $events;
        $this->events = clone $repository->getEvents();
        $this->selection = new SelectionQuery($this);
    }

    public static function fromSelection(Selection $selection, Repository $repository, bool $events = true): Query
    {
        $query = new self($repository, $events);
        $query->selection = $selection;
        return $query;
    }

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

    public function getPrimary()
    {
        return $this->selection->getPrimary();
    }

    /************************** Modification methods **************************/
    /**
     * @param Entity ...$entities
     * @return bool|int|Entity
     */
    public function insert(Entity ...$entities)
    {
        $result = $this->selection->insert($entities);
        if ($result instanceof ActiveRow) {
            return $this->repository->toEntity($result);
        }
        return $result;
    }


    /************************** Fetching methods **************************/
    /**
     * @return Generator<E>
     */
    public function getIterator(): Generator
    {
        foreach ($this->selection as $row) {
            yield $this->repository->toEntity($row);
        }
        yield from [];
    }

    /**
     * @return E|null
     */
    public function fetch(): ?Entity
    {
        $row = $this->selection->fetch();
        if (!$row) {
            return null;
        }
        return $this->repository->toEntity($row);
    }

    /**
     * @return Entity[]
     */
    public function fetchAll(): array
    {
        return iterator_to_array($this->getIterator());
    }

    public function fetchPairs($key = null, $value = null): array
    {
        $pairs = [];
        foreach ($this->selection->fetchPairs($key, $value) as $iKey => $iValue) {
            if ($iValue instanceof ActiveRow) {
                $iValue = $this->repository->toEntity($iValue);
            }
            $pairs[$iKey] = $iValue;
        }
        return $pairs;
    }

    /****************** Pass-thru **************************/
    public function __call(string $name, $arguments)
    {
        // if iterator method, use entity iterator
        if (in_array($name, ['current', 'key', 'next', 'rewind', 'valid'])) {
            $this->innerIterator ??= new CachingIterator($this->getIterator());
            return $this->innerIterator->$name(...$arguments);
        }
        return $this->selection->$name(...$arguments);
    }
}
