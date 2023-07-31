<?php

namespace Efabrica\NetteRepository\Repository;

use Efabrica\NetteRepository\Traits\RepositoryBehavior;

class RepositoryBehaviors
{
    private array $behaviors = [];

    public function add(RepositoryBehavior $behavior, ?string $key = null): self
    {
        if ($key === null) {
            if (!isset($this->behaviors[get_class($behavior)])) {
                $this->behaviors[get_class($behavior)] = $behavior;
            } else {
                $this->behaviors[] = $behavior;
            }
        } else {
            $this->behaviors[$key] = $behavior;
        }
        return $this;
    }

    public function replace(RepositoryBehavior $behavior, ?string $key = null): self
    {
        if ($key === null) {
            $this->behaviors[get_class($behavior)] = $behavior;
        } else {
            $this->behaviors[$key] = $behavior;
        }
        return $this;
    }

    /**
     * @return RepositoryBehavior[]
     */
    public function all(): array
    {
        return $this->behaviors;
    }

    /**
     * @template T of RepositoryBehavior
     * @param class-string<T> $behaviorClass
     * @return T
     */
    public function get(string $behaviorClass): ?RepositoryBehavior
    {
        if (isset($this->behaviors[$behaviorClass]) && $this->behaviors[$behaviorClass] instanceof $behaviorClass) {
            return $this->behaviors[$behaviorClass];
        }
        foreach ($this->behaviors as $behavior) {
            if ($behavior instanceof $behaviorClass) {
                return $behavior;
            }
        }
        return null;
    }

    public function has(string $behaviorClass): bool
    {
        return $this->get($behaviorClass) !== null;
    }

    /**
     * @param class-string<RepositoryBehavior> $behaviorClass
     * @return $this
     */
    public function remove(string $behaviorClass): self
    {
        foreach ($this->behaviors as $key => $behavior) {
            if ($behavior instanceof $behaviorClass) {
                unset($this->behaviors[$key]);
            }
        }

        return $this;
    }

    /**
     * @param string $key
     * @return $this
     */
    public function removeKey(string $key): self
    {
        unset($this->behaviors[$key]);
        return $this;
    }
}
