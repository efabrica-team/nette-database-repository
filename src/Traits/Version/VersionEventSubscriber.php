<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Version;

use Efabrica\IrisClient\IrisUser;
use Efabrica\NetteDatabaseRepository\Event\DeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Event\InsertEventResponse;
use Efabrica\NetteDatabaseRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Repository\Query;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteSubscriber;
use Nette\DI\Container;
use Nette\Utils\Json;

class VersionEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    protected IrisUser $irisUser;

    protected string $transactionId;

    private ?VersionRepository $versionRepository;

    public function __construct(Container $container, IrisUser $irisUser)
    {
        $this->versionRepository = $container->getByType(VersionRepository::class, false);
        $this->irisUser = $irisUser;
        $this->transactionId = uniqid('', true);
    }

    public function supportsRepository(Repository $repository): bool
    {
        return $repository->behaviors()->has(VersionBehavior::class);
    }

    /**
     * @param positive-int $limit
     * @param int<0,max> $offset
     */
    public function getVersionsForRecord(Repository $repository, string $id, int $limit = 10, int $offset = 0): Query
    {
        return $this->versionRepository->findBy([
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
        $this->versionRepository->insert(...$versions);
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
        $this->versionRepository->insert(...$versions);
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
        $this->versionRepository->query()->where(['col']);
        $this->versionRepository->insert(...$versions);
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

        $version = $this->versionRepository->createRow();
        $version->foreign_id = $entity->getPrimary();
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
     * ['pages' => $record->getPageId()]
     */
    protected function getRelatedTables(Entity $entity): array
    {
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
        $existing = $this->versionRepository->findOneBy([
            'transaction_id' => $this->transactionId,
            'foreign_id' => $foreignId,
            'foreign_table' => $table,
        ]);

        if ($existing) {
            return $existing;
        }

        $entity = $this->versionRepository->createRow();
        $entity->foreign_id = $foreignId;
        $entity->foreign_table = $table;
        $entity->flag = 'update';
        $entity->transaction_id = $this->transactionId;
        $entity->linked_id = $recordToLink->id ?? null;
        $this->versionRepository->insert($entity);
        return $entity;
    }
}
