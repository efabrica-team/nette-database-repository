<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Account;

use Efabrica\IrisClient\IrisUser;
use Efabrica\NetteDatabaseRepository\Event\InsertEventResponse;
use Efabrica\NetteDatabaseRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Event\SelectQueryEvent;
use Efabrica\NetteDatabaseRepository\Event\SelectQueryResponse;
use Efabrica\NetteDatabaseRepository\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;

class AccountEventSubscriber extends EventSubscriber
{
    private IrisUser $irisUser;

    public function __construct(IrisUser $irisUser)
    {
        $this->irisUser = $irisUser;
    }

    public function supportsRepository(Repository $repository): bool
    {
        return $repository->behaviors()->has(AccountBehavior::class);
    }

    private function getAccountId(): ?string
    {
        if (count($this->irisUser->getAccounts()) > 0) {
            return (string)$this->irisUser->getAccounts()[0];
        }
        return null;
    }

    public function onSelect(SelectQueryEvent $event): SelectQueryResponse
    {
        /** @var AccountBehavior $behavior */
        $behavior = $event->getRepository()->behaviors()->get(AccountBehavior::class);
        $field = $behavior->getAccountField();

        $permissions = $this->irisUser->getByKey('permissions');
        if (isset($permissions['superuser'])) {
            return $event->handle();
        }

        $query = $event->getQuery();
        if (count($this->irisUser->getAccounts()) === 0) {
            $query->where($query->getRepository()->getTableName() . '.' . $field, null);
        } else {
            $query->where($query->getRepository()->getTableName() . '.' . $field, $this->irisUser->getAccounts());
        }
        return $event->handle();
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        /** @var AccountBehavior $behavior */
        $behavior = $event->getRepository()->behaviors()->get(AccountBehavior::class);
        $field = $behavior->getAccountField();

        $permissions = $this->irisUser->getByKey('permissions');
        if (isset($permissions['superuser'])) {
            return $event->handle();
        }

        foreach ($event->getEntities() as $entity) {
            if (!isset($entity->$field)) {
                $entity->$field = $this->getAccountId();
            }
        }

        return $event->handle();
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        /** @var AccountBehavior $behavior */
        $behavior = $event->getRepository()->behaviors()->get(AccountBehavior::class);
        $field = $behavior->getAccountField();

        if (array_key_exists($field, $data) && empty($data[$field])) {
            $data[$field] = null;
        } else {
            $data[$field] = $this->getAccountId();
        }
        return $event->handle($data);
    }
}
