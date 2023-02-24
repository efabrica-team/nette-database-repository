<?php

namespace Efabrica\NetteDatabaseRepository\Selections;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Models\Managers\ModelFactoryManagerInterface;
use Nette\Caching\Storage;
use Nette\Database\Conventions;
use Nette\Database\Explorer;
use Nette\Database\Table\Selection as BaseSelection;

/**
 * @property ActiveRow[] $data
 * @property ActiveRow[] $rows
 * @method bool|int|ActiveRow insert(iterable $data)
 * @method ActiveRow|null get(mixed $key)
 * @method ActiveRow|null fetch()
 * @method ActiveRow[] fetchAll()
 */
class Selection extends BaseSelection
{
    use RepositorySelection;

    protected RepositoryManagerInterface $repositoryManager;

    protected ModelFactoryManagerInterface $modelFactoryManager;

    public function __construct(RepositoryManagerInterface $repositoryManager, ModelFactoryManagerInterface $modelFactoryManager, Explorer $explorer, Conventions $conventions, string $tableName, Storage $cacheStorage = null)
    {
        parent::__construct($explorer, $conventions, $tableName, $cacheStorage);
        $this->repositoryManager = $repositoryManager;
        $this->modelFactoryManager = $modelFactoryManager;
    }
}
