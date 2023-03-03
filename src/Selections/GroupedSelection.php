<?php

namespace Efabrica\NetteDatabaseRepository\Selections;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Models\Managers\ModelFactoryManagerInterface;
use Efabrica\NetteDatabaseRepository\Repositores\Managers\RepositoryManagerInterface;
use Iterator;
use Nette\Caching\Storage;
use Nette\Database\Conventions;
use Nette\Database\Explorer;
use Nette\Database\Table\GroupedSelection as BaseGroupedSelection;
use Nette\Database\Table\Selection as BaseSelection;

/**
 * @template M of ActiveRow
 * @template-implements Iterator<int, ActiveRow>
 *
 * @property M[] $data
 * @property M[] $rows
 * @method bool|int|ActiveRow|M insert(iterable $data)
 * @method ActiveRow|M|null get(mixed $key)
 * @method ActiveRow|M|null fetch()
 * @method ActiveRow|M[] fetchAll()
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
