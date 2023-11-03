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

    protected Scope $current;

    public function __construct()
    {
        $this->full = new FullScope();
        $this->raw = new RawScope();
        $this->current = $this->full;
    }

    public function apply(RepositoryBehaviors $behaviors): void
    {
        $this->current->apply($behaviors);
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
