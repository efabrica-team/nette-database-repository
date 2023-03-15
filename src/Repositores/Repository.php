<?php

namespace Efabrica\NetteDatabaseRepository\Repositores;

use Efabrica\NetteDatabaseRepository\Behavior\BehaviorInjector;
use Efabrica\NetteDatabaseRepository\Exceptions\RepositoryException;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Selections\Factories\SelectionFactoryInterface;
use Efabrica\NetteDatabaseRepository\Selections\Selection;
use Nette\Database\Explorer;
use PDOException;
use Throwable;
use Traversable;

/**
 * @template S of Selection
 * @template M of ActiveRow
 */
abstract class Repository
{
    protected Explorer $explorer;

    protected SelectionFactoryInterface $selectionFactory;

    protected RepositoryBehaviors $behaviors;

    public function __construct(Explorer $db, SelectionFactoryInterface $selectionFactory, BehaviorInjector $behaviorInjector)
    {
        $this->explorer = $db;
        $this->selectionFactory = $selectionFactory;
        $this->behaviors = new RepositoryBehaviors($behaviorInjector);
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

    public function getBehaviors(): RepositoryBehaviors
    {
        return $this->behaviors;
    }

    /**
     * @return self Repository without behaviors
     */
    public function raw(): self
    {
        $raw = clone $this;
        $raw->behaviors = new RepositoryBehaviors();
        return $raw;
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
        $inTransaction = false;
        try {
            $inTransaction = $this->getExplorer()->getConnection()->getPdo()->inTransaction();
            if (!$inTransaction) {
                $this->getExplorer()->beginTransaction();
            }

            $data = $data instanceof Traversable ? iterator_to_array($data) : $data;
            foreach ($this->behaviors as $behavior) {
                $behavior->beforeInsert($data);
            }
            $record = $this->getSelection()->insert($data);
            foreach ($this->behaviors as $behavior) {
                $behavior->afterInsert($record, $data);
            }

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

    /**
     * @throws Throwable
     * @throws RepositoryException
     */
    public function insertMany(array $items): int
    {
        foreach ($items as $item) {
            $this->insert($item);
        }
        return count($items);
    }

    /**
     * @param M|int|string $record
     * @param iterable     $data
     *
     * @return M|null
     * @throws RepositoryException
     * @throws Throwable
     */
    public function update($record, iterable $data): ?ActiveRow
    {
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
            foreach ($this->behaviors as $behavior) {
                $data = $behavior->beforeUpdate($recordToUpdate, $data);
            }
            $oldModel = clone $recordToUpdate;
            $recordToUpdate->originalUpdate($data);
            foreach ($data as $key => $value) {
                $recordToUpdate->$key = $value;
            }
            foreach ($this->behaviors as $behavior) {
                $behavior->afterUpdate($oldModel, $recordToUpdate, $data);
            }

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
            foreach ($this->behaviors as $behavior) {
                $abort = $behavior->beforeDelete($recordToDelete);
                if ($abort !== null) {
                    return $abort;
                }
            }
            $result = $recordToDelete->originalDelete();
            foreach ($this->behaviors as $behavior) {
                $behavior->afterDelete($oldRecord);
            }

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
            $record = $this->getSelection()->get($record);
        }
        return $record ?: null;
    }

    public function __clone()
    {
        $this->behaviors = clone $this->behaviors;
    }
}
