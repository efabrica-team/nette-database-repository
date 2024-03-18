<?php

namespace Efabrica\NetteRepository\Traits\TreeTraverse;

use Efabrica\NetteRepository\Event\DeleteEventResponse;
use Efabrica\NetteRepository\Event\DeleteQueryEvent;
use Efabrica\NetteRepository\Event\InsertEventResponse;
use Efabrica\NetteRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteRepository\Event\RepositoryEvent;
use Efabrica\NetteRepository\Event\UpdateEventResponse;
use Efabrica\NetteRepository\Event\UpdateQueryEvent;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Subscriber\EventSubscriber;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteEventResponse;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteQueryEvent;
use Efabrica\NetteRepository\Traits\SoftDelete\SoftDeleteSubscriber;

class TreeTraverseEventSubscriber extends EventSubscriber implements SoftDeleteSubscriber
{
    public function supportsEvent(RepositoryEvent $event): bool
    {
        return $event->hasBehavior(TreeTraverseBehavior::class);
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        $response = $event->handle();
        $this->onTreeStructure($event->getRepository());
        return $response;
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): UpdateEventResponse
    {
        $response = $event->handle($data);
        $this->onTreeStructure($event->getRepository());
        return $response;
    }

    public function onDelete(DeleteQueryEvent $event): DeleteEventResponse
    {
        $response = $event->handle();
        $this->onTreeStructure($event->getRepository());
        return $response;
    }

    public function onSoftDelete(SoftDeleteQueryEvent $event, array &$data): SoftDeleteEventResponse
    {
        $response = $event->handle($data);
        $this->onTreeStructure($event->getRepository());
        return $response;
    }

    protected function onTreeStructure(Repository $repository): void
    {
        /** @var TreeTraverseBehavior $behavior */
        $behavior = $repository->getBehaviors()->get(TreeTraverseBehavior::class);
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

        $repository->update($id, [
            $behavior->getLeftColumn() => $lft,
            $behavior->getRightColumn() => $value + 2,
            $behavior->getDepthColumn() => $depth,
        ]);
        return $value + 1;
    }
}
