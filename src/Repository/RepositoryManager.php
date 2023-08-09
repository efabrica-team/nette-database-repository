<?php

namespace Efabrica\NetteRepository\Repository;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Efabrica\NetteRepository\CodeGen\ModuleWriter;
use Nette\DI\Container;
use RuntimeException;

class RepositoryManager
{
    private array $repositories = [];

    private Container $container;

    private Inflector $inflector;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->inflector = InflectorFactory::create()->build();
    }

    /**
     * @template R of Repository
     * @param class-string<R> $class
     * @return R
     */
    public function byClass(string $class): Repository
    {
        return $this->repositories[$class] ??= $this->container->getByType($class);
    }

    public function byTableName(string $table): Repository
    {
        $repo = $this->container->getByName(ModuleWriter::toRepoServiceName($table, $this->inflector));
        assert($repo instanceof Repository);
        if ($repo->getTableName() !== $table) {
            throw new RuntimeException("When looking for repository for table $table, found repository for table {$repo->getTableName()} (".get_class($repo).")");
        }
        return $this->repositories[$table] ??= $repo;
    }

    /**
     * @param object|string $repository
     * @param string $trait
     * @return bool
     */
    public static function hasTrait($repository, string $trait): bool
    {
        foreach ([-1 => $repository] + class_parents($repository) as $parent) {
            if (in_array($trait, class_uses($parent) ?: [], true)) {
                return true;
            }
        }
        return false;
    }
}
