<?php

namespace Efabrica\NetteDatabaseRepository\Selections;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;

trait RepositorySelection
{
    use SelectionBehavior;

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

        foreach ($repository->getBehaviors() as $behavior) {
            $behavior->beforeSelect($this);
        }

        parent::execute();

        foreach ($repository->getBehaviors() as $behavior) {
            $behavior->afterSelect($this);
        }
    }

    protected function createRow(array $row): ActiveRow
    {
        return $this->modelFactoryManager->createForTable($this->getName())->create($row, $this);
    }

    public function createSelectionInstance(?string $table = null): Selection
    {
        return new Selection($this->repositoryManager, $this->modelFactoryManager, $this->explorer, $this->conventions, $table ?: $this->name, $this->cache->getStorage());
    }

    protected function createGroupedSelectionInstance(string $table, string $column): GroupedSelection
    {
        return new GroupedSelection($this->repositoryManager, $this->modelFactoryManager, $this->explorer, $this->conventions, $table ?: $this->name, $column, $this, $this->cache->getStorage());
    }
}
