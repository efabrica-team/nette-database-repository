<?php

namespace Efabrica\NetteDatabaseRepository\Casts\Factories;

use Efabrica\NetteDatabaseRepository\Casts\CastInterface;
use Nette\DI\Container;
use RuntimeException;

final class CastFactory
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @template C of CastInterface
     *
     * @param class-string<C> $type
     *
     * @return C
     */
    public function createFromType(string $type, array $args = []): CastInterface
    {
        $cast = $this->container->createInstance($type, $args);
        if (!$cast instanceof CastInterface) {
            throw new RuntimeException('Cast must implement "' . CastInterface::class . '".');
        }
        return $cast;
    }
}
