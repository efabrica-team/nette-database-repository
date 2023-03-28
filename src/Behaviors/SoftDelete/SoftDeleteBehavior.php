<?php

namespace Efabrica\NetteDatabaseRepository\Behaviors\SoftDelete;

use Efabrica\NetteDatabaseRepository\Behaviors\RepositoryBehavior;
use Efabrica\NetteDatabaseRepository\Enums\HookType;
use Efabrica\NetteDatabaseRepository\Exceptions\RepositoryException;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\DateTime;
use PDOException;
use Throwable;

trait SoftDeleteBehavior
{
    use RepositoryBehavior;

    public function deletedAtField(): string
    {
        return 'deleted_at';
    }

    final public function defaultConditionsWhereNotDeleted(Selection $selection): void
    {
        $selection->where($this->getTableName() . '.' . $this->deletedAtField() . ' IS NULL');
    }

    /**
     * @param int|string|ActiveRow $record
     *
     * @return bool
     * @throws Throwable
     */
    public function delete($record): bool
    {
        $this->ignoreHookType(HookType::DEFAULT_CONDITIONS);

        $recordToDelete = $this->getRecord($record);

        $hookIgnores = $this->getHookIgnores();
        $this->resetHookIgnores();

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
            $this->callMethods(HookType::BEFORE_SOFT_DELETE, ['record' => $record], $hookIgnores);
            $result = $this
                ->ignoreHookType(HookType::BEFORE_UPDATE)
                ->ignoreHookType(HookType::AFTER_UPDATE)
                ->update($recordToDelete, [
                    $this->deletedAtField() => new DateTime(),
                ]);
            $this->callMethods(HookType::AFTER_SOFT_DELETE, ['record' => $oldRecord], $hookIgnores);

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
     * @param int|string|ActiveRow $record
     *
     * @return bool
     * @throws Throwable
     */
    public function restore($record): bool
    {
        $this->ignoreHookType(HookType::DEFAULT_CONDITIONS);

        $recordToRestore = $this->getRecord($record);

        $hookIgnores = $this->getHookIgnores();
        $this->resetHookIgnores();

        if ($recordToRestore === null) {
            return false;
        }

        $inTransaction = false;
        try {
            $inTransaction = $this->getExplorer()->getConnection()->getPdo()->inTransaction();
            if (!$inTransaction) {
                $this->getExplorer()->beginTransaction();
            }

            $oldRecord = clone $recordToRestore;
            $this->callMethods(HookType::BEFORE_RESTORE, ['record' => $record], $hookIgnores);
            $result = $this
                ->ignoreHookType(HookType::BEFORE_UPDATE)
                ->ignoreHookType(HookType::AFTER_UPDATE)
                ->update($recordToRestore, [
                    $this->deletedAtField() => null,
                ]);
            $this->callMethods(HookType::AFTER_RESTORE, ['record' => $oldRecord], $hookIgnores);

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
     * @param int|string|ActiveRow $record
     *
     * @return bool
     * @throws Throwable
     */
    public function forceDelete($record): bool
    {
        return parent::delete($record);
    }
}
