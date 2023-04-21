<?php

namespace Efabrica\NetteDatabaseRepository\Bridge\DI;

use Efabrica\NetteDatabaseRepository\Casts\Factories\CastFactory;
use Efabrica\NetteDatabaseRepository\Casts\JsonArrayCast;
use Efabrica\NetteDatabaseRepository\Helpers\CallableAutowirer;
use Efabrica\NetteDatabaseRepository\Models\Factories\ModelFactoryInterface;
use Efabrica\NetteDatabaseRepository\Models\Factories\SelectionFactoryManager;
use Efabrica\NetteDatabaseRepository\Models\Managers\ModelFactoryManager;
use Efabrica\NetteDatabaseRepository\Repositores\Managers\RepositoryManager;
use Efabrica\NetteDatabaseRepository\Selections\Factories\SelectionFactoryInterface;
use Nette\DI\CompilerExtension;

class NetteDatabaseRepositoryExtension extends CompilerExtension
{
    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('repositoryManager'))
            ->setFactory(RepositoryManager::class);

        $builder->addDefinition($this->prefix('selectionFactoryManager'))
            ->setFactory(SelectionFactoryManager::class, ['defaultSelectionFactory' => $this->prefix('@selectionFactory')]);

        $builder->addDefinition($this->prefix('modelFactoryManager'))
            ->setFactory(ModelFactoryManager::class, ['defaultModelFactory' => $this->prefix('@modelFactory')]);

        $builder->addFactoryDefinition($this->prefix('selectionFactory'))
            ->setImplement(SelectionFactoryInterface::class)
            ->setAutowired(SelectionFactoryInterface::class);

        $builder->addFactoryDefinition($this->prefix('modelFactory'))
            ->setImplement(ModelFactoryInterface::class)
            ->setAutowired(ModelFactoryInterface::class);

        $builder->addDefinition($this->prefix('castFactory'))
            ->setFactory(CastFactory::class);

        $builder->addDefinition(null)
            ->setFactory(JsonArrayCast::class);

        $builder->addDefinition(null)
            ->setFactory(CallableAutowirer::class);
    }
}
