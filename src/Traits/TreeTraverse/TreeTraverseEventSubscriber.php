<?php

namespace Efabrica\NetteDatabaseRepository\Traits\TreeTraverse;

use Efabrica\NetteDatabaseRepository\Model\EntityMeta;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\DeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertEventResponse;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteSubscriber;

class TreeTraverseEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    protected function getLeftColumnName(Repository $repository): string
    {
        $prop = EntityMeta::getAnnotatedProperty($repository->getEntityClass(), '@TreeLeft');
        return $prop !== null ? $prop->getName() : 'lft';
    }

    protected function getRightColumnName(Repository $repository): string
    {
        $prop = EntityMeta::getAnnotatedProperty($repository->getEntityClass(), '@TreeRight');
        return $prop !== null ? $prop->getName() : 'rgt';
    }

    protected function getDepthColumnName(Repository $repository): string
    {
        $prop = EntityMeta::getAnnotatedProperty($repository->getEntityClass(), '@TreeDepth');
        return $prop !== null ? $prop->getName() : 'depth';
    }

    protected function getParentColumnName(Repository $repository): string
    {
        $prop = EntityMeta::getAnnotatedProperty($repository->getEntityClass(), '@TreeParent');
        return $prop !== null ? $prop->getName() : 'parent_id';
    }

    protected function getSortingColumnName(Repository $repository): string
    {
        $prop = EntityMeta::getAnnotatedProperty($repository->getEntityClass(), '@TreeSorting');
        return $prop !== null ? $prop->getName() : 'sorting';
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        $response = $event->handle();
        $this->onTreeStructure($event->getRepository());
        return $response;
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        $response = $event->handle($data);
        $this->onTreeStructure($event->getRepository());
        return $response;
    }

    public function onDelete(DeleteQueryEvent $event): int
    {
        $response = $event->handle();
        $this->onTreeStructure($event->getRepository());
        return $response;
    }

    public function onSoftDelete(SoftDeleteQueryEvent $event, array &$data): int
    {
        return $this->onUpdate($event, $data);
    }

    protected function onTreeStructure(Repository $repository): void
    {
        $select = 'id, ' . $this->getParentColumnName($repository) . ', ' . $this->getSortingColumnName($repository);
        $order = $this->getSortingColumnName($repository);

        $treeStructure = [];
        foreach ($repository->query()->select($select)->order($order) as $row) {
            if (!isset($treeStructure[$row[$this->getParentColumnName($repository)]])) {
                $treeStructure[$row[$this->getParentColumnName($repository)]] = [];
            }
            $treeStructure[$row[$this->getParentColumnName($repository)]][] = $row['id'];
        }
        $this->updateTreeStructure($treeStructure, $repository);
    }

    /**
     * Update tree structure, one request per row :-(
     * @param int|null $id
     * @param int      $value
     * @param int      $depth
     * @return int
     */
    protected function updateTreeStructure(array &$treeStructure, Repository $repository, int $id = null, int $value = 0, int $depth = 1): int
    {
        // Update structure from null parent (root)
        if ($id === null) {
            if (isset($treeStructure[$id])) {
                foreach ($treeStructure[$id] as $subId) {
                    $value = $this->updateTreeStructure($treeStructure, $repository, $subId, $value, $depth);
                    $value++;
                }
            }

            return $value;
        }

        $lft = $value + 1;
        if (isset($treeStructure[$id])) {
            foreach ($treeStructure[$id] as $subId) {
                $value = $this->updateTreeStructure($treeStructure, $repository, $subId, $value + 1, $depth + 1);
            }
        }

        $entity = $repository->find($id);
        if ($entity !== null) {
            $entity[$this->getLeftColumnName($repository)] = $lft;
            $entity[$this->getRightColumnName($repository)] = $value + 2;
            $entity[$this->getDepthColumnName($repository)] = $depth;
            $repository->update($entity);
        }
        return $value + 1;
    }
}
