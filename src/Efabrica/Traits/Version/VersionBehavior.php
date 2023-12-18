<?php

namespace Efabrica\NetteRepository\Efabrica\Traits\Version;

use Closure;
use Efabrica\NetteRepository\Repository\Entity;
use Efabrica\NetteRepository\Traits\RepositoryBehavior;

class VersionBehavior extends RepositoryBehavior
{
    private array $ignoreColumns = [];
    private array $forceColumns = [];

    private ?Closure $relatedTables = null;

    public function setIgnoreColumns(string ...$columns): self
    {
        $this->ignoreColumns = $columns;
        return $this;
    }

    public function setForceColumns(string ...$columns): self
    {
        $this->forceColumns = $columns;
        return $this;
    }

    /**
     * ex. 1: if you want to create a version entry for a page from a record which has a fk to the pages table
     *  ['pages' => $entity->page_id]
     * @param callable(Entity $entity): array<string,scalar> $relatedTables
     * @return $this
     */
    public function setRelatedTables(callable $relatedTables): self
    {
        $this->relatedTables = Closure::fromCallable($relatedTables);
        return $this;
    }

    public function getIgnoreColumns(): array
    {
        return $this->ignoreColumns;
    }

    public function getForceColumns(): array
    {
        return $this->forceColumns;
    }

    public function getRelatedTables(Entity $entity): array
    {
        return $this->relatedTables !== null ? ($this->relatedTables)($entity) : [];
    }
}
