<?php

namespace Efabrica\NetteDatabaseRepository\Model;

use Efabrica\NetteDatabaseRepository\Repository\Query;
use IteratorAggregate;
use Traversable;

/**
 * @template E of Entity
 * This servers to encapsulate result of Entity->relToMany() method.
 */
class EntityCollection implements IteratorAggregate
{
    /** @var Query<E> */
    private Query $query;

    /**
     * @param Query<E> $query
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * @return E[]
     */
    public function toArray(): array
    {
        return $this->query->fetchAll();
    }

    /**
     * @return Traversable<E>
     */
    public function getIterator(): Traversable
    {
        return $this->query->getIterator();
    }
}
