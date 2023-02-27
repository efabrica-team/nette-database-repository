<?php

namespace Efabrica\NetteDatabaseRepository\Repositores;

use Efabrica\NetteDatabaseRepository\Exceptions\RepositoryException;
use Efabrica\NetteDatabaseRepository\Helpers\CallableAutowirer;
use Efabrica\NetteDatabaseRepository\Helpers\HasHookIgnores;
use Efabrica\NetteDatabaseRepository\Helpers\HookIgnore;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Selections\Factories\SelectionFactoryInterface;
use Efabrica\NetteDatabaseRepository\Selections\Selection;
use Nette\Database\Explorer;
use PDOException;
use ReflectionClass;
use ReflectionMethod;
use Throwable;
use Traversable;

/**
 * @template S of Selection
 * @template M of ActiveRow
 */
abstract class Repository
{
    use HasHookIgnores;

    protected Explorer $explorer;

    protected SelectionFactoryInterface $selectionFactory;

    protected CallableAutowirer $callableAutowire;

    public function __construct(Explorer $db, SelectionFactoryInterface $selectionFactory, CallableAutowirer $callableAutowire)
    {
        $this->explorer = $db;
        $this->selectionFactory = $selectionFactory;
        $this->callableAutowire = $callableAutowire;
    }

    abstract public function getTableName(): string;

    final public function getExplorer(): Explorer
    {
        return $this->explorer;
    }

    /**
     * @return S
     */
    public function getSelection(): Selection
    {
        return $this->selectionFactory->create($this->getTableName());
    }

    /**
     * @return S
     */
    public function query(): Selection
    {
        $hookIgnores = $this->getHookIgnores();
        $this->resetHookIgnores();

        return $this->getSelection()->importHookIgnores($hookIgnores);
    }

    /**
     * @param iterable $data
     *
     * @return bool|int|M
     * @throws RepositoryException
     * @throws Throwable
     */
    public function insert(iterable $data)
    {
        $this->ignoreHookType('defaultConditions');
        $hookIgnores = $this->getHookIgnores();
        $this->resetHookIgnores();

        $inTransaction = false;
        try {
            $inTransaction = $this->getExplorer()->getConnection()->getPdo()->inTransaction();
            if (!$inTransaction) {
                $this->getExplorer()->beginTransaction();
            }

            $data = $data instanceof Traversable ? iterator_to_array($data) : $data;
            $data = $this->beforeInsert($data);
            $record = $this->query()->importHookIgnores($hookIgnores)->insert($data);
            $this->callMethods('afterInsert', ['record' => $record, 'data' => $data], $this->hookIgnores);

            if (!$inTransaction) {
                $this->getExplorer()->commit();
            }
        } catch (Throwable $e) {
            if (!$inTransaction && ($e instanceof RepositoryException || $e instanceof PDOException)) {
                $this->getExplorer()->rollBack();
            }
            throw $e;
        }

        return $record;
    }

    public function insertMany(array $items): int
    {
        $hookIgnores = $this->getHookIgnores();
        $this->resetHookIgnores();

        $count = 0;
        foreach ($items as $item) {
            $this->importHookIgnores($hookIgnores)->insert($item);
            $count++;
        }
        return $count;
    }

    /**
     * @param M|int|string $record
     * @param iterable $data
     *
     * @return M|null
     * @throws RepositoryException
     * @throws Throwable
     */
    public function update($record, iterable $data): ?ActiveRow
    {
        $this->ignoreHookType('defaultConditions');
        $hookIgnores = $this->getHookIgnores();
        $this->resetHookIgnores();

        $recordToUpdate = $this->getRecord($record);
        if ($recordToUpdate === null) {
            return null;
        }

        $inTransaction = false;
        try {
            $inTransaction = $this->getExplorer()->getConnection()->getPdo()->inTransaction();
            if (!$inTransaction) {
                $this->getExplorer()->beginTransaction();
            }

            $data = $data instanceof Traversable ? iterator_to_array($data) : $data;
            $data = $recordToUpdate->castDataToSet($data);
            $data = $this->beforeUpdate($recordToUpdate, $data);
            $oldModel = clone $recordToUpdate;
            $recordToUpdate->originalUpdate($data, $hookIgnores);
            foreach ($data as $key => $value) {
                $recordToUpdate->$key = $value;
            }
            $this->callMethods('afterUpdate', ['oldRecord' => $oldModel, 'newRecord' => $recordToUpdate, 'data' => $data], $this->hookIgnores);

            if (!$inTransaction) {
                $this->getExplorer()->commit();
            }
        } catch (Throwable $e) {
            if (!$inTransaction && ($e instanceof RepositoryException || $e instanceof PDOException)) {
                $this->getExplorer()->rollBack();
            }
            throw $e;
        }
        return $recordToUpdate;
    }

    /**
     * @param M|int|string $record
     *
     * @return bool
     * @throws Throwable
     */
    public function delete($record): bool
    {
        $this->ignoreHookType('defaultConditions');
        $hookIgnores = $this->getHookIgnores();
        $this->resetHookIgnores();

        $recordToDelete = $this->getRecord($record);
        if ($recordToDelete === null) {
            return false;
        }

        $inTransaction = false;
        try {
            $inTransaction = $this->getExplorer()->getConnection()->getPdo()->inTransaction();
            if (!$inTransaction) {
                $this->getExplorer()->beginTransaction();
            }

            $oldRecord = clone $recordToDelete;
            $this->callMethods('beforeDelete', ['record' => $recordToDelete], $this->hookIgnores);
            $result = $recordToDelete->originalDelete($hookIgnores);
            $this->callMethods('afterDelete', ['record' => $oldRecord], $this->hookIgnores);

            if (!$inTransaction) {
                $this->getExplorer()->commit();
            }
        } catch (Throwable $e) {
            if (!$inTransaction && ($e instanceof RepositoryException || $e instanceof PDOException)) {
                $this->getExplorer()->rollBack();
            }
            throw $e;
        }
        return (bool)$result;
    }

    /**
     * @param $record
     *
     * @return M|null
     */
    protected function getRecord($record): ?ActiveRow
    {
        if (!$record instanceof ActiveRow) {
            $record = $this->query()->get($record);
        }
        return $record ?: null;
    }

    /**
     * @param HookIgnore[] $hookIgnores
     */
    protected function findMethods(string $methodPrefix, array $hookIgnores = []): array
    {
        $repositoryReflection = new ReflectionClass($this);
        $methodReflections = $repositoryReflection->getMethods(ReflectionMethod::IS_FINAL);

        $result = [];
        foreach ($methodReflections as $methodReflection) {
            if ($methodReflection->isPublic() && strpos($methodReflection->getName(), $methodPrefix) === 0) {
                foreach ($hookIgnores as $hookIgnore) {
                    if ($hookIgnore->isCallableIgnored($repositoryReflection, $methodReflection)) {
                        continue 2;
                    }
                }
                $methodSuffix = lcfirst(substr($methodReflection->getName(), strlen($methodPrefix)));
                $result[$methodSuffix] = $methodReflection->getName();
            }
        }

        return $result;
    }

    /**
     * @param HookIgnore[] $hookIgnores
     */
    public function callMethods(string $methodPrefix, array $args, array $hookIgnores = []): bool
    {
        $methods = $this->findMethods($methodPrefix, $hookIgnores);
        foreach ($methods as $methodName) {
            $callable = [$this, $methodName];
            if (is_callable($callable)) {
                $this->callableAutowire->callMethod($callable, $args);
            }
        }

        return true;
    }

    private function beforeInsert(array $data): array
    {
        $methods = $this->findMethods('beforeInsert', $this->hookIgnores);
        foreach ($methods as $methodName) {
            $callable = [$this, $methodName];
            if (is_callable($callable)) {
                /** @var array $data */
                $data = $this->callableAutowire->callMethod($callable, ['data' => $data]);
            }
        }
        return $data;
    }

    private function beforeUpdate(ActiveRow $record, array $data): array
    {
        $methods = $this->findMethods('beforeUpdate', $this->hookIgnores);
        foreach ($methods as $methodName) {
            $callable = [$this, $methodName];
            if (is_callable($callable)) {
                /** @var array $data */
                $data = $this->callableAutowire->callMethod($callable, ['record' => $record, 'data' => $data]);
            }
        }
        return $data;
    }
}
