<?php

namespace Efabrica\NetteDatabaseRepository\Model;

use Traversable;

trait EntityAccess
{
    /**
     * Iterates over all private properties of the entity.
     * @return Traversable<string, mixed>
     */
    public function getIterator(): Traversable
    {
        return EntityMeta::toIterable($this);
    }

    /**
     * Dumps all private properties of the entity to an array.
     * @return array<string, mixed>
     */
    final public function toArray(): array
    {
        return EntityMeta::toArray($this);
    }

    /**
     * Sets all private properties of the entity from an array.
     * @param iterable $data
     * @return EntityAccess|Entity
     */
    public function set(iterable $data): self
    {
        EntityMeta::populate($this, $data);
        return $this;
    }

    /**
     * Checks if a private property of the entity exists.
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return EntityMeta::isSet($this, $offset);
    }

    /**
     * Gets a property of the entity by calling the related getter.
     * @param $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return EntityMeta::get($this, $offset);
    }

    /**
     * Sets a property of the entity by calling the related setter.
     * @param $offset
     * @param $value
     */
    public function offsetSet($offset, $value): void
    {
        EntityMeta::set($this, $offset, $value);
    }

    /**
     * Sets a property of the entity to null by calling the related setter.
     * @param $offset
     */
    public function offsetUnset($offset): void
    {
        $this->offsetSet($offset, null);
    }
}
