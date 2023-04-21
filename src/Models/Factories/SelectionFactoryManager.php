<?php

namespace Efabrica\NetteDatabaseRepository\Models\Factories;

use Efabrica\NetteDatabaseRepository\Selections\Factories\ClassSelectionFactory;
use Efabrica\NetteDatabaseRepository\Selections\Factories\SelectionFactoryInterface;
use Efabrica\NetteDatabaseRepository\Selections\Managers\SelectionFactoryManagerInterface;
use Nette\DI\Container;

final class SelectionFactoryManager implements SelectionFactoryManagerInterface
{
    private Container $container;

    private SelectionFactoryInterface $defaultSelectionFactory;

    private array $factories = [];

    public function __construct(Container $container, SelectionFactoryInterface $defaultSelectionFactory)
    {
        $this->container = $container;
        $this->defaultSelectionFactory = $defaultSelectionFactory;
    }

    public function addClass(string $repository, string $selection): self
    {
        $this->factories[$repository] = new ClassSelectionFactory($this->container, $selection);
        return $this;
    }

    public function addFactory(string $repository, SelectionFactoryInterface $selectionFactory): self
    {
        $this->factories[$repository] = $selectionFactory;
        return $this;
    }

    public function unsetFactory(string $repository): self
    {
        unset($this->factories[$repository]);
        return $this;
    }

    public function createForRepository(string $repository): SelectionFactoryInterface
    {
        return $this->factories[$repository] ?? $this->defaultSelectionFactory;
    }
}
