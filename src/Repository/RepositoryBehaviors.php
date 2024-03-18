<?php

namespace Efabrica\NetteRepository\Repository;

use ArrayIterator;
use Efabrica\NetteRepository\Repository\Scope\FullScope;
use Efabrica\NetteRepository\Repository\Scope\Scope;
use Efabrica\NetteRepository\Repository\Scope\ScopeContainer;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;
use IteratorAggregate;

class RepositoryBehaviors implements IteratorAggregate
{
    private array $behaviors = [];

    private Repository $repository;

    private ScopeContainer $scope;

    private ?array $scoped = null;

    public function __construct(Repository $repository, ScopeContainer $scope)
    {
        $this->repository = $repository;
        $this->scope = $scope;
    }

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
        $this->scoped = null;
        return $this;
    }

    public function replace(RepositoryBehavior $behavior, ?string $key = null): self
    {
        if ($key === null) {
            $this->behaviors[get_class($behavior)] = $behavior;
        } else {
            $this->behaviors[$key] = $behavior;
        }
        $this->scoped = null;
        return $this;
    }

    /**
     * @return RepositoryBehavior[]
     */
    public function all(): array
    {
        if ($this->getScope() instanceof FullScope) {
            return $this->allRaw();
        }
        if ($this->scoped === null) {
            $scoped = new RepositoryBehaviors($this->repository, $this->scope->full());
            $scoped->behaviors = $this->behaviors;
            $this->scope->apply($scoped);
            $this->scoped = $scoped->behaviors;
        }
        return $this->scoped;
    }

    public function allRaw(): array
    {
        return $this->behaviors;
    }

    /**
     * @template T
     * @param class-string<T> $behaviorClass
     * @return T&RepositoryBehavior
     */
    public function get(string $behaviorClass, bool $ignoreScope = false): ?RepositoryBehavior
    {
        $behaviors = $ignoreScope ? $this->allRaw() : $this->all();
        if (isset($behaviors[$behaviorClass]) && $behaviors[$behaviorClass] instanceof $behaviorClass) {
            return $behaviors[$behaviorClass];
        }
        foreach ($behaviors as $behavior) {
            if ($behavior instanceof $behaviorClass) {
                return $behavior;
            }
        }
        return null;
    }

    /**
     * @param class-string $behaviorClass
     * @return bool
     */
    public function has(string $behaviorClass): bool
    {
        return $this->get($behaviorClass) !== null;
    }

    /**
     * @param class-string $behaviorClass
     * @return $this
     */
    public function remove(string $behaviorClass): self
    {
        foreach ($this->behaviors as $key => $behavior) {
            if ($behavior instanceof $behaviorClass) {
                $this->removeKey($key);
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
        if (isset($this->behaviors[$key])) {
            unset($this->behaviors[$key]);
            $this->scoped = null;
        }
        return $this;
    }

    public function removeAll(): self
    {
        $this->behaviors = [];
        $this->scoped = null;
        return $this;
    }

    /**
     * @internal does not clone self
     * @param Scope $scope
     */
    public function setScope(Scope $scope): void
    {
        $this->scope = $this->scope->withScope($scope);
        $this->scoped = null;
    }

    public function getScopeContainer(): ScopeContainer
    {
        return $this->scope;
    }

    public function getScope(): Scope
    {
        $scope = $this->scope;
        while ($scope instanceof ScopeContainer) {
            $scope = $scope->current();
        }
        return $scope;
    }

    public function withScope(Scope $scope): self
    {
        $clone = clone $this;
        $clone->setScope($scope);
        return $clone;
    }

    /**
     * @param class-string<Scope> $class
     * @return bool
     */
    public function isScope(string $class): bool
    {
        $scope = $this->scope;
        while ($scope instanceof ScopeContainer) {
            if ($scope instanceof $class) {
                return true;
            }
            $scope = $scope->current();
        }
        return $scope instanceof $class;
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    /**
     * @return ArrayIterator<RepositoryBehavior>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->all());
    }
}
