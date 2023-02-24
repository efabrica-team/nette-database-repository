<?php

namespace Efabrica\NetteDatabaseRepository\Selections;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Models\Managers\ModelFactoryManagerInterface;
use Nette\Caching\Storage;
use Nette\Database\Conventions;
use Nette\Database\Explorer;
use Nette\Database\Table\GroupedSelection as BaseGroupedSelection;
use Nette\Database\Table\Selection as BaseSelection;

/**
 * @property ActiveRow[] $data
 * @property ActiveRow[] $rows
 * @method bool|int|ActiveRow insert(iterable $data)
 * @method ActiveRow|null get(mixed $key)
 * @method ActiveRow|null fetch()
 * @method ActiveRow[] fetchAll()
 */
class GroupedSelection extends BaseGroupedSelection
{
    use RepositorySelection;

    protected RepositoryManagerInterface $repositoryManager;

    protected ModelFactoryManagerInterface $modelFactoryManager;

    public function __construct(RepositoryManagerInterface $repositoryManager, ModelFactoryManagerInterface $modelFactoryManager, Explorer $explorer, Conventions $conventions, string $tableName, string $column, BaseSelection $refTable, Storage $cacheStorage = null)
    {
        parent::__construct($explorer, $conventions, $tableName, $column, $refTable, $cacheStorage);
        $this->repositoryManager = $repositoryManager;
        $this->modelFactoryManager = $modelFactoryManager;
    }
}
