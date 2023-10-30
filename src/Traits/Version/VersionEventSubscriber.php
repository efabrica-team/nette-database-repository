<?php

namespace Efabrica\NetteRepository\Traits\Version;

use Efabrica\IrisClient\IrisUser;
use Efabrica\NetteRepository\Event\DeleteQueryEvent;
use Efabrica\NetteRepository\Event\InsertEventResponse;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Event\UpdateQueryEvent;
use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\Query;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Repository\RepositoryManager;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;
use Efabrica\NetteRepository\Traits\Date\DateBehavior;
use Efabrica\NetteRepository\Traits\Owner\OwnerBehavior;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteSubscriber;
use Nette\Utils\Json;
use SplObjectStorage;

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
        if ($this->versionRepository === null) {
            $this->versionRepository = $this->repositoryManager->byTableName(self::TableName);
            $behaviors = $this->versionRepository->getBehaviors();
            if ($behaviors->all() === []) {
                $behaviors->add(new DateBehavior(Version::CREATED_AT, null));
                $behaviors->add(new OwnerBehavior(Version::USER_ID, null));
            }
        }
        return $this->versionRepository;
    }

    public function supportsEvent(RepositoryEvent $event): bool
    {
        return $event->hasBehavior(VersionBehavior::class);
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

    /*********************************************************************\
     * After/before methods
     * *******************************************************************/

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        $result = $event->handle();
        $versions = [];
        foreach ($event->getEntities() as $entity) {
            $versions[] = $this->createVersion($entity, $entity->toArray(), 'create');
        }
        $this->getVersionRepository()->insert($versions);
        return $result;
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        $entities = new SplObjectStorage();
        foreach ($event->getEntities() as $entity) {
            $entities[$entity] = clone $entity;
        }
        $result = $event->handle($data);
        $versions = [];
        foreach ($event->getEntities() as $newEntity) {
            $oldEntity = $entities[$newEntity];
            $versions[] = $this->createVersion($oldEntity, (clone $oldEntity)->fill($newEntity)->diff(), 'update');
        }
        $this->getVersionRepository()->insert($versions);
        return $result;
    }

    public function onDelete(DeleteQueryEvent $event): int
    {
        $result = $event->handle();
        $versions = [];
        foreach ($event->getEntities() as $entity) {
            $versions[] = $this->createVersion($entity, [], 'delete');
        }
        $this->getVersionRepository()->insert($versions);
        return $result;
    }

    public function onSoftDelete(SoftDeleteQueryEvent $event, array &$data): int
    {
        $entities = $event->getQuery()->fetchAll();
        $result = $event->handle($data);
        $versions = [];
        foreach ($entities as $entity) {
            $versions[] = $this->createVersion($entity, $data, 'soft-delete');
        }
        $this->getVersionRepository()->insert($versions);
        return $result;
    }

    /********************************************************************\
     * | Protected methods
     * \********************************************************************/

    protected function createVersion(Entity $entity, array $newData, string $flag): Version
    {
        $oldData = [];
        if ($flag === 'update') {
            foreach ($newData as $key => $value) {
                $oldData[$key] = $entity[$key];
            }
        } elseif ($flag === 'delete') {
            $oldData = $entity->toArray();
            $newData = [];
        } elseif ($flag === 'soft-delete') {
            $oldData = $entity->toArray();
        }

        $recordToLink = $this->processLinkedEntries($entity);
        $primary = $entity->getPrimary();
        assert(is_scalar($primary));

        $version = $this->getVersionRepository()->createRow();
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

        $version = $this->getVersionRepository()->createRow();
        $version->foreign_id = $foreignId;
        $version->foreign_table = $table;
        $version->flag = 'update';
        $version->transaction_id = $this->transactionId;
        $version->linked_id = $recordToLink->id ?? null;
        return $version->save();
    }
}
