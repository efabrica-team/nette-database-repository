<?php

namespace Efabrica\NetteDatabaseRepository\Traits\TreeTraverse;

use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\DeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertEntityEventResponse;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteSubscriber;

class TreeTraverseEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    protected function getLeftColumnName(): string
    {
        return 'lft';
    }

    protected function getRightColumnName(): string
    {
        return 'rgt';
    }

    protected function getDepthColumnName(): string
    {
        return 'depth';
    }

    protected function getParentColumnName(): string
    {
        return 'parent_id';
    }

    protected function getSortingColumnName(): string
    {
        return 'sorting';
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEntityEventResponse
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

    public function softDelete(SoftDeleteQueryEvent $event, array &$data): int
    {
        return $this->onUpdate($event, $data);
    }

    protected function onTreeStructure(Repository $repository): void
    {
        $select = 'id, ' . $this->getParentColumnName() . ', ' . $this->getSortingColumnName();
        $order = $this->getSortingColumnName();

        $this->treeStructure = [];
        foreach ($repository->query()->select($select)->order($order) as $row) {
            if (!isset($this->treeStructure[$row->parent_id])) {
                $this->treeStructure[$row->parent_id] = [];
            }
            $this->treeStructure[$row->parent_id][] = $row->id;
        }
        $this->updateTreeStructure($repository);
    }

    /**
     * Update tree structure, one request per row :-(
     * @param int|null $id
     * @param int      $value
     * @param int      $depth
     * @return int
     */
    protected function updateTreeStructure(Repository $repository, int $id = null, int $value = 0, int $depth = 1): int
    {
        // Update structure from null parent (root)
        if ($id === null) {
            if (isset($this->treeStructure[$id])) {
                foreach ($this->treeStructure[$id] as $subId) {
                    $value = $this->updateTreeStructure($subId, $value, $depth);
                    $value++;
                }
            }

            return $value;
        }

        $lft = $value + 1;
        if (isset($this->treeStructure[$id])) {
            foreach ($this->treeStructure[$id] as $subId) {
                $value = $this->updateTreeStructure($subId, $value + 1, $depth + 1);
            }
        }

        $entity = $repository->find($id);
        if ($entity !== null) {
            $entity[$this->getLeftColumnName()] = $lft;
            $entity[$this->getRightColumnName()] = $value + 2;
            $entity[$this->getDepthColumnName()] = $depth;
            $repository->update($entity);
        }
        return $value + 1;
    }
}
