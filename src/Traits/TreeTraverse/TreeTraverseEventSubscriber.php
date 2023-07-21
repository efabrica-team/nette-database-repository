<?php

namespace Efabrica\NetteDatabaseRepository\Traits\TreeTraverse;

use Efabrica\NetteDatabaseRepository\Event\DeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Event\InsertEventResponse;
use Efabrica\NetteDatabaseRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteDatabaseRepository\Traits\SoftDelete\SoftDeleteSubscriber;

class TreeTraverseEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    public function supportsRepository(Repository $repository): bool
    {
        return $repository->behaviors()->has(TreeTraverseBehavior::class);
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
        /** @var TreeTraverseBehavior $behavior */
        $behavior = $repository->behaviors()->get(TreeTraverseBehavior::class);
        $order = $behavior->getSortingColumn();
        $select = 'id, ' . $behavior->getParentColumn() . ', ' . $order;

        $treeStructure = [];
        foreach ($repository->query()->select($select)->order($order) as $row) {
            if (!isset($treeStructure[$row[$behavior->getParentColumn()]])) {
                $treeStructure[$row[$behavior->getParentColumn()]] = [];
            }
            $treeStructure[$row[$behavior->getParentColumn()]][] = $row['id'];
        }
        $this->updateTreeStructure($treeStructure, $repository, $behavior);
    }

    protected function updateTreeStructure(
        array &$treeStructure,
        Repository $repository,
        TreeTraverseBehavior $behavior,
        int $id = null,
        int $value = 0,
        int $depth = 1
    ): int {
        // Update structure from null parent (root)
        if ($id === null) {
            if (isset($treeStructure[$id])) {
                foreach ($treeStructure[$id] as $subId) {
                    $value = $this->updateTreeStructure($treeStructure, $repository, $behavior, $subId, $value, $depth);
                    $value++;
                }
            }

            return $value;
        }

        $lft = $value + 1;
        if (isset($treeStructure[$id])) {
            foreach ($treeStructure[$id] as $subId) {
                $value = $this->updateTreeStructure($treeStructure, $repository, $behavior, $subId, $value + 1, $depth + 1);
            }
        }

        $entity = $repository->find($id);
        if ($entity !== null) {
            $entity[$behavior->getLeftColumn()] = $lft;
            $entity[$behavior->getRightColumn()] = $value + 2;
            $entity[$behavior->getDepthColumn()] = $depth;
            $repository->update($entity);
        }
        return $value + 1;
    }
}
