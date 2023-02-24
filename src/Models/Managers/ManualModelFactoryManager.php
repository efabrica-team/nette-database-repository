<?php

namespace Efabrica\NetteDatabaseRepository\Models\Managers;

use Efabrica\NetteDatabaseRepository\Models\Factories\ModelFactoryInterface;

final class ManualModelFactoryManager implements ModelFactoryManagerInterface
{
    private ModelFactoryInterface $defaultModelFactory;

    private array $factories = [];

    public function __construct(ModelFactoryInterface $defaultModelFactory)
    {
        $this->defaultModelFactory = $defaultModelFactory;
    }

    public function addFactory(string $table, ModelFactoryInterface $modelFactory): self
    {
        $this->factories[$table] = $modelFactory;
        return $this;
    }

    public function createForTable(string $table): ModelFactoryInterface
    {
        return $this->factories[$table] ?? $this->defaultModelFactory;
    }
}
