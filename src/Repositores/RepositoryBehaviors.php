<?php

namespace Efabrica\NetteDatabaseRepository\Repositores;

use ArrayIterator;
use Efabrica\NetteDatabaseRepository\Behavior\Behavior;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<Behavior>
 */
final class RepositoryBehaviors implements IteratorAggregate
{
    /** @var Behavior[] */
    private array $behaviors = [];

    public function add(Behavior $behavior, ?string $key = null): self
    {
        $this->behaviors[$key ?? get_class($behavior)] = $behavior;
        return $this;
    }

    public function get(string $key): ?Behavior
    {
        return $this->behaviors[$key] ?? null;
    }

    public function remove(string $key): self
    {
        unset($this->behaviors[$key]);
        return $this;
    }

    /**
     * @param class-string $class
     */
    public function removeClass(string $class): void
    {
        foreach ($this->behaviors as $key => $behavior) {
            if ($behavior instanceof $class) {
                unset($this->behaviors[$key]);
            }
        }
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->behaviors);
    }

    public function __clone()
    {
        foreach ($this->behaviors as $key => $value) {
            $this->behaviors[$key] = clone $value;
        }
    }
}