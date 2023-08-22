<?php

namespace Efabrica\NetteRepository\Traits\Version;

use Efabrica\IrisClient\IrisUser;
use Efabrica\NetteRepository\Event\DeleteQueryEvent;
use Efabrica\NetteRepository\Event\InsertEventResponse;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteRepository\Event\UpdateQueryEvent;
use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Repository\RepositoryManager;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteSubscriber;
use Nette\Utils\Json;

class VersionEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    public const TableName = 'versions';

    protected IrisUser $irisUser;

    protected string $transactionId;

    private ?Repository $versionRepository = null;

    private RepositoryManager $repositoryManager;

    public function __construct(RepositoryManager $repositoryManager, IrisUser $irisUser)
    {
        $this->irisUser = $irisUser;
        $this->transactionId = uniqid('', true);
        $this->repositoryManager = $repositoryManager;
    }

    /**
     * @return Repository<Version,Query<Version>>
     */
    private function getVersionRepository(): Repository
    {
        return $this->versionRepository ??= $this->repositoryManager->byTableName(self::TableName);
    }

    public function supportsRepository(Repository $repository): bool
    {
        return $repository->behaviors()->has(VersionBehavior::class);
    }

    /**
     * @param positive-int $limit
     * @param int<0,max>   $offset
     */
    public function getVersionsForRecord(Repository $repository, string $id, int $limit = 10, int $offset = 0): Query
    {
        return $this->getVersionRepository()->findBy([
            'foreign_id' => $id,
            'foreign_table' => $repository->getTableName(),
        ])->order('created_at DESC')->limit($limit, $offset);
    }

    /********************************************************************\
     * | After/before methods
     * \********************************************************************/

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        $result = $event->handle();
        $versions = [];
        foreach ($event->getEntities() as $entity) {
            $versions[] = $this->insertVersion($entity, [], 'create');
        }
        $this->getVersionRepository()->insert(...$versions);
        return $result;
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        $entities = $event->getQuery()->fetchAll();
        $result = $event->handle($data);
        foreach ($entities as $entity) {
            $this->insertVersion($entity, $data, 'update');
        }
        return $result;
    }

    public function onDelete(DeleteQueryEvent $event): int
    {
        $entities = $event->getQuery()->fetchAll();
        $result = $event->handle();
        $versions = [];
        foreach ($entities as $entity) {
            $versions[] = $this->insertVersion($entity, [], 'delete');
        }
        $this->getVersionRepository()->insert(...$versions);
        return $result;
    }

    public function onSoftDelete(SoftDeleteQueryEvent $event, array &$data): int
    {
        $entities = $event->getQuery()->fetchAll();
        $result = $event->handle($data);
        $versions = [];
        foreach ($entities as $entity) {
            $versions[] = $this->insertVersion($entity, $data, 'soft-delete');
        }
        $this->getVersionRepository()->insert(...$versions);
        return $result;
    }

    /********************************************************************\
     * | Protected methods
     * \********************************************************************/

    protected function insertVersion(Entity $entity, array $newData, string $flag): Version
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
        $primary = $entity->getPrimary();
        assert(is_scalar($primary));

        $version = $this->getVersionRepository()->create();
        $version->foreign_id = (string)$primary;
        $version->foreign_table = $entity->getTableName();
        $version->old_data = Json::encode($oldData);
        $version->new_data = Json::encode($newData);
        $version->flag = $flag;
        $version->transaction_id = $this->transactionId;
        $version->linked_id = $recordToLink->id ?? null;
        return $version;
    }

    /**
     * Configuration for the related tables [and foreign ID-s],
     * where a version entry should be created
     *
     * ex. 1: if you want to create a version entry for a page from a record which has a fk to the pages table
     * ['pages' => $entity->page_id]
     * @return array<string,scalar>
     */
    protected function getRelatedTables(Entity $entity): array
    {
        // TODO move to behavior
        return [];
    }

    /********************************************************************\
     * | Private (help) methods
     * \********************************************************************/

    /**
     * Create linked version entries for related tables
     */
    private function processLinkedEntries(Entity $entity): ?Version
    {
        $recordToLink = null;

        foreach ($this->getRelatedTables($entity) as $table => $foreignId) {
            $recordToLink = $this->processLinkedEntry((string)$foreignId, $table, $recordToLink);
        }

        return $recordToLink;
    }

    /**
     * Insert one linked version entry to a related table
     * @param string   $foreignId
     * @param string   $table
     * @param ?Version $recordToLink
     * @return Version
     */
    private function processLinkedEntry(string $foreignId, string $table, Version $recordToLink = null): Version
    {
        $existing = $this->getVersionRepository()->findOneBy([
            'transaction_id' => $this->transactionId,
            'foreign_id' => $foreignId,
            'foreign_table' => $table,
        ]);

        if ($existing !== null) {
            return $existing;
        }

        $entity = $this->getVersionRepository()->create();
        $entity->foreign_id = $foreignId;
        $entity->foreign_table = $table;
        $entity->flag = 'update';
        $entity->transaction_id = $this->transactionId;
        $entity->linked_id = $recordToLink->id ?? null;
        $this->getVersionRepository()->insert($entity);
        return $entity;
    }
}
