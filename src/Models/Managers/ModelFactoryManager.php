<?php

namespace Efabrica\NetteDatabaseRepository\Models\Managers;

use Efabrica\NetteDatabaseRepository\Models\Factories\ClassModelFactory;
use Efabrica\NetteDatabaseRepository\Models\Factories\ModelFactoryInterface;
use Nette\DI\Container;

final class ModelFactoryManager implements ModelFactoryManagerInterface
{
    private Container $container;

    private ModelFactoryInterface $defaultModelFactory;

    private array $factories = [];

    public function __construct(Container $container, ModelFactoryInterface $defaultModelFactory)
    {
        $this->container = $container;
        $this->defaultModelFactory = $defaultModelFactory;
    }

    public function addClass(string $table, string $activeRow): self
    {
        $this->factories[$table] = new ClassModelFactory($this->container, $activeRow);
        return $this;
    }

    public function addFactory(string $table, ModelFactoryInterface $modelFactory): self
    {
        $this->factories[$table] = $modelFactory;
        return $this;
    }

    public function unsetFactory(string $table): self
    {
        unset($this->factories[$table]);
        return $this;
    }

    public function createForTable(string $table): ModelFactoryInterface
    {
        return $this->factories[$table] ?? $this->defaultModelFactory;
    }
}
