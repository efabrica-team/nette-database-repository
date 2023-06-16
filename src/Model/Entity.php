<?php

namespace Efabrica\NetteDatabaseRepository\Model;

use ArrayAccess;
use IteratorAggregate;

/**
 * @implements ArrayAccess<string, mixed> Allows to access entity properties by calling getters and setters internally.
 * @implements IteratorAggregate<string, mixed> Allows to iterate over entity properties and their values.
 */
abstract class Entity implements IteratorAggregate, ArrayAccess
{
    use EntityAccess;

    /**
     * Each entity has its own relations object that caches the results of the relations.
     * The relations object is injected by the repository query.
     * @var EntityRelations|null $rel
     */
    private ?EntityRelations $rel = null;

    protected function relToOne(bool $cached, string $repositoryClass, $value, $column = null): ?Entity
    {
        return $this->rel !== null ? $this->rel->relToOne($cached, $repositoryClass, $value, $column) : null;
    }

    protected function relToMany(bool $cached, string $repositoryClass, $value, $column = null): iterable
    {
        return $this->rel !== null ? $this->rel->relToMany($cached, $repositoryClass, $value, $column) : [];
    }
}
