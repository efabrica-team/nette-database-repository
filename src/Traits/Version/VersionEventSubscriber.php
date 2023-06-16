<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Version;

use Efabrica\IrisClient\IrisUser;
use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Repository\Query;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\DeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertEntityEventResponse;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteSubscriber;

class VersionEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    protected IrisUser $irisUser;

    protected string $transactionId;

    private array $relatedTables = [];

    private VersionRepository $versionRepository;

    public function __construct(VersionRepository $versionRepository, IrisUser $irisUser)
    {
        $this->versionRepository = $versionRepository;
        $this->irisUser = $irisUser;
        $this->transactionId = uniqid('', true);
    }

    public function getVersionsForRecord(Repository $repository, $id, int $limit = 10, int $offset = 0): Query
    {
        return $this->versionRepository->findBy([
            'foreign_id' => $id,
            'foreign_table' => $repository->getTableName(),
        ])->order('created_at DESC')->limit($limit, $offset);
    }

    /********************************************************************\
     * | After/before methods
     * \********************************************************************/

    public function onInsert(InsertRepositoryEvent $event): InsertEntityEventResponse
    {
        $result = $event->handle();
        // Unset ignored data
        if (isset($this->versionIgnore)) {
            foreach ($this->versionIgnore as $ignored) {
                if (isset($data[$ignored])) {
                    unset($data[$ignored]);
                }
            }
        }
        $this->insertVersion($event->getEntity(), $event->getRepository()->getTableName(), $data, 'create');
        return $result;
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        $entities = $event->getQuery()->fetchAll();
        $result = $event->handle($data);
        foreach ($entities as $entity) {
            $this->insertVersion($entity, $event->getRepository()->getTableName(), $data, 'update');
        }
        return $result;
    }

    public function onDelete(DeleteQueryEvent $event): int
    {
        $entities = $event->getQuery()->fetchAll();
        $result = $event->handle();
        foreach ($entities as $entity) {
            $this->insertVersion($entity, $event->getRepository()->getTableName(), [], 'delete');
        }
        return $result;
    }

    public function softDelete(SoftDeleteQueryEvent $event, array &$data): int
    {
        $entities = $event->getQuery()->fetchAll();
        $result = $event->handle($data);
        foreach ($entities as $entity) {
            $this->insertVersion($entity, $event->getRepository()->getTableName(), [], 'soft-delete');
        }
        return $result;
    }

    /********************************************************************\
     * | Protected methods
     * \********************************************************************/

    protected function insertVersion(Entity $entity, string $table, array $newData, string $flag): void
    {
        $oldData = [];
        if ($flag === 'update') {
            foreach ($newData as $key => $value) {
                $oldData[$key] = $entity[$key];
            }
        } elseif ($flag === 'delete') {
            $oldData = $entity->toArray();
            $newData = [];
        }

        $recordToLink = $this->processLinkedEntries($entity);

        $version = (new Version())
            ->setForeignId($entity->getId())
            ->setForeignTable($table)
            ->setOldData($oldData)
            ->setNewData($newData)
            ->setFlag($flag)
            ->setTransactionId($this->transactionId)
            ->setLinkedId($recordToLink ? $recordToLink->getId() : null)
        ;
        $this->versionRepository->insert($version);
    }

    /**
     * Configuration for the related tables [and foreign ID-s],
     * where a version entry should be created
     *
     * ex. 1: if you want to create a version entry for a page from a record which has a fk to the pages table
     * ['pages' => $record->getPageId()]
     */
    protected function getRelatedTables(Entity $entity): array
    {
        return [];
    }

    /********************************************************************\
     * | Private (help) methods
     * \********************************************************************/

    private function makeDiff(Entity $entity, array $data): array
    {
        $result = [
            'old' => [],
            'new' => [],
        ];

        $operationMarks = ['+=', '-='];
        foreach ($data as $column => $value) {
            if ($value instanceof Entity) {
                $value = (string)$value;
            }

            //If is used operation mark, move it from column to the value
            if (!isset($entity[$column])) {
                foreach ($operationMarks as $operationMark) {
                    if (strpos($column, $operationMark)) {
                        $column = rtrim($column, $operationMark);
                        $value = $operationMark . $value;
                    }
                }
            }

            // If value was changed
            // We can use get_defined_vars() to check if $record->$column exists but i think it is overkill
            // isset is unusable for this purpose because function returns false if variable $record->$column is NULL
            if ($record->$column != $value) {
                // If key is not ignored
                if ((isset($this->versionIgnore) && !in_array($column, $this->versionIgnore)) || !isset($this->versionIgnore)) {
                    $result['old'][$column] = $record->$column === null ? null : $this->convertToString($record->$column);
                    $result['new'][$column] = $value === null ? null : $this->convertToString($value);
                }
            }
        }

        return $result;
    }

    /**
     * Convert input to string
     * @param mixed $value
     * @return string
     */
    private function convertToString($value): string
    {
        if ($value instanceof DateTimeInterface) {
            return (string)DateTime::from($value);
        } elseif ($value instanceof DateInterval) {
            return $value->format('%r%h:%I:%S');
        }

        return (string)$value;
    }

    /**
     * Create linked version entries for related tables
     */
    private function processLinkedEntries(Version $record): ?Version
    {
        $recordToLink = null;

        $relatedTables = empty($this->relatedTables) ? $this->getRelatedTables($record) : $this->relatedTables;

        foreach ($relatedTables as $table => $foreignId) {
            $recordToLink = $this->processLinkedEntry($foreignId, $table, $recordToLink);
        }

        return $recordToLink;
    }

    /**
     * Insert one linked version entry to a related table
     * @param mixed    $foreignId
     * @param string   $table
     * @param ?Version $recordToLink
     * @return Version
     */
    private function processLinkedEntry($foreignId, string $table, Version $recordToLink = null): Version
    {
        $existing = $this->versionRepository->findBy([
            'transaction_id' => $this->transactionId,
            'foreign_id' => $foreignId,
            'foreign_table' => $table,
        ])->fetch();

        if ($existing) {
            return $existing;
        }

        $entity = (new Version())
            ->setForeignId($foreignId)
            ->setForeignTable($table)
            ->setFlag('update')
            ->setTransactionId($this->transactionId)
            ->setLinked($recordToLink)
        ;
        $this->versionRepository->insert($entity);
        return $entity;
    }

    /**
     * Saves the related table values, before the record is deleted (for later use in the afterDelete methods)
     * @param ActiveRow $oldRecord
     */
    private function cacheRelatedRows(ActiveRow $oldRecord): void
    {
        $this->relatedTables = $this->getRelatedTables($oldRecord);
    }
}
