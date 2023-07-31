<?php

namespace Efabrica\NetteRepository\Repository;

use Nette\DI\Container;

class RepositoryManager
{
    private array $repositories = [];

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @template R of Repository
     * @param class-string<R> $class
     * @return R
     */
    public function getRepository(string $class): Repository
    {
        return $this->repositories[$class] ??= $this->container->getByType($class);
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
