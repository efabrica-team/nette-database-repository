<?php

namespace Efabrica\NetteRepository\Repository;

use Efabrica\NetteRepository\Repository\Scope\FullScope;
use Efabrica\NetteRepository\Repository\Scope\Scope;
use Efabrica\NetteRepository\Repository\Scope\ScopeContainer;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;

class RepositoryBehaviors
{
    private array $behaviors = [];

    private Repository $repository;

    private ScopeContainer $scope;

    private ?self $scoped = null;

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
            $this->scoped = clone $this;
            $this->scoped->scope = $this->scope->full();
            $this->scoped->scoped = null;
            $this->scope->apply($this->scoped, $this->repository);
        }
        return $this->scoped->all();
    }

    public function allRaw(): array
    {
        return $this->behaviors;
    }

    /**
     * @template T of RepositoryBehavior
     * @param class-string<T> $behaviorClass
     * @return T
     */
    public function get(string $behaviorClass, bool $raw = false): ?RepositoryBehavior
    {
        $behaviors = $raw ? $this->allRaw() : $this->all();
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
     * @param class-string<RepositoryBehavior> $behaviorClass
     * @return bool
     */
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

    public function setScope(Scope $scope): self
    {
        $this->scope = $this->scope->withScope($scope);
        $this->scoped = null;
        return $this;
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
        $scope = $this;
        while ($scope instanceof ScopeContainer) {
            if ($scope instanceof $class) {
                return true;
            }
            $scope = $scope->current();
        }
        return $scope instanceof $class;
    }
}