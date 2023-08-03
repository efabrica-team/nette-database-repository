<?php

namespace Efabrica\NetteRepository\Repository\Scope;

use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Repository\RepositoryBehaviors;

/**
 * @immutable
 */
class ScopeContainer implements Scope
{
    private FullScope $full;

    private RawScope $raw;

    private Scope $current;

    /**
     * @param FullScope  $fullScope
     * @param Scope|null $current
     * @phpstan-consistent-constructor
     */
    public function __construct(FullScope $fullScope, ?Scope $current = null)
    {
        $this->full = $fullScope;
        $this->current = $current ?? $this->full;
        $this->raw = new RawScope();
    }

    public function apply(RepositoryBehaviors $behaviors, Repository $repository): void
    {
        $this->current->apply($behaviors, $repository);
    }

    public function withScope(Scope $scope): self
    {
        if ($scope === $this->current) {
            return $this;
        }

        $clone = clone $this;
        $clone->current = $scope;
        return $clone;
    }

    public function full(): self
    {
        return $this->withScope($this->full);
    }

    public function raw(): self
    {
        return $this->withScope($this->raw);
    }

    public function current(): Scope
    {
        return $this->current;
    }
}
