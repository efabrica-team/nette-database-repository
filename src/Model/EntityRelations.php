<?php

namespace Efabrica\NetteDatabaseRepository\Model;

use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Repository\RepositoryManager;

/**
 * This class is used to obtain relations between entities and caches them per entity instance.
 */
class EntityRelations
{
    private RepositoryManager $repositoryManager;

    private array $manyCache = [];

    private array $oneCache = [];

    public function __construct(RepositoryManager $repositoryManager)
    {
        $this->repositoryManager = $repositoryManager;
    }

    /**
     * @template E of Entity
     * @param bool                        $cached Whether the result can be loaded from cache
     * @param class-string<Repository<E>> $repositoryClass Repository from which to load the entity
     * @param mixed                       $value Value to use in where() clause
     * @param ?string                     $column Column to search in. If null, primary key is used.
     * @param bool                        $events Whether to fire events
     * @return EntityCollection
     */
    public function relToMany(bool $cached, string $repositoryClass, $value, ?string $column = null, bool $events = true): EntityCollection
    {
        if ($cached && isset($this->manyCache[$repositoryClass][$value][$column ?? ''])) {
            return $this->manyCache[$repositoryClass][$value][$column ?? ''];
        }
        $repository = $this->repositoryManager->getRepository($repositoryClass);
        if ($column === null) {
            $result = new EntityCollection($repository->query($events)->wherePrimary($value));
        } else {
            $result = new EntityCollection($repository->query($events)->where($column, $value));
        }
        return $this->manyCache[$repositoryClass][$value][$column ?? ''] = $result;
    }

    /**
     * @template E of Entity
     * @param bool                        $cached Whether the result can be loaded from cache
     * @param class-string<Repository<E>> $repositoryClass Repository from which to load the entity
     * @param mixed                       $value Value to use in where() clause
     * @param ?string                     $column Column to search in. If null, primary key is used.
     * @param bool                        $events Whether to fire events
     * @return E|null
     */
    public function relToOne(bool $cached, string $repositoryClass, $value, ?string $column = null, bool $events = true): ?Entity
    {
        if ($cached && isset($this->oneCache[$repositoryClass][$value][$column ?? ''])) {
            return $this->oneCache[$repositoryClass][$value][$column ?? ''] ?: null;
        }
        foreach ($this->relToMany($cached, $repositoryClass, $value, $column, $events) as $entity) {
            return $this->oneCache[$repositoryClass][$value][$column ?? ''] = $entity;
        }
        $this->oneCache[$repositoryClass][$value][$column ?? ''] = false;
        return null;
    }
}
