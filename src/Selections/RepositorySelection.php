<?php

namespace Efabrica\NetteDatabaseRepository\Selections;

use Efabrica\NetteDatabaseRepository\Enums\HookType;
use Efabrica\NetteDatabaseRepository\Helpers\HasHookIgnores;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;

trait RepositorySelection
{
    use HasHookIgnores;

    protected function execute(): void
    {
        if ($this->rows !== null) {
            return;
        }

        $repository = $this->repositoryManager->createForTable($this->getName());
        if ($repository === null) {
            parent::execute();
            return;
        }

        $repository->callMethods(HookType::DEFAULT_CONDITIONS, ['selection' => $this], $this->hookIgnores);
        $repository->callMethods(HookType::BEFORE_SELECT, ['selection' => $this], $this->hookIgnores);

        parent::execute();

        $repository->callMethods(HookType::AFTER_SELECT, ['selection' => $this], $this->hookIgnores);
    }

    protected function createRow(array $row): ActiveRow
    {
        return $this->modelFactoryManager->createForTable($this->getName())->create($row, $this);
    }

    public function createSelectionInstance(?string $table = null): Selection
    {
        $selection = new Selection($this->repositoryManager, $this->modelFactoryManager, $this->explorer, $this->conventions, $table ?: $this->name, $this->cache->getStorage());

        if ($this->getName() === $selection->getName()) {
            $selection->importHookIgnores($this->getHookIgnores());
        }

        return $selection;
    }

    protected function createGroupedSelectionInstance(string $table, string $column): GroupedSelection
    {
        return new GroupedSelection($this->repositoryManager, $this->modelFactoryManager, $this->explorer, $this->conventions, $table ?: $this->name, $column, $this, $this->cache->getStorage());
    }
}
