<?php

namespace Efabrica\NetteDatabaseRepository\Traits\Account;

use Efabrica\IrisClient\IrisUser;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\AnnotationReader;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\InsertEntityEventResponse;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\SelectQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\SelectQueryResponse;
use Efabrica\NetteDatabaseRepository\Subscriber\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;

class AccountEventSubscriber extends EventSubscriber
{
    public const ANNOTATION = '@AccountId';

    private IrisUser $irisUser;

    private AnnotationReader $annotationReader;

    public function __construct(IrisUser $irisUser, AnnotationReader $annotationReader)
    {
        $this->irisUser = $irisUser;
        $this->annotationReader = $annotationReader;
    }

    public function supportsRepository(Repository $repository): bool
    {
        return $this->annotationReader->findProperty($repository->getEntityClass(), self::ANNOTATION) !== null;
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
        $field = $this->annotationReader->findProperty($event->getEntityClass(), self::ANNOTATION);
        if ($field === null) {
            return $event->handle();
        }
        $permissions = $this->irisUser->getByKey('permissions');
        if (isset($permissions['superuser'])) {
            return $event->handle();
        }

        $query = $event->getQuery();
        if (count($this->irisUser->getAccounts()) === 0) {
            $query->where($query->getRepository()->getTableName() . '.' . $field->getName(), null);
        } else {
            $query->where($query->getRepository()->getTableName() . '.' . $field->getName(), $this->irisUser->getAccounts());
        }
        return $event->handle();
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEntityEventResponse
    {
        $prop = $this->annotationReader->findProperty($event->getEntityClass(), self::ANNOTATION);
        if ($prop !== null) {
            foreach ($event->getEntities() as $entity) {
                $prop->setValue($entity, $this->getAccountId());
            }
        }
        return $event->handle();
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        $field = $this->annotationReader->findProperty($event->getEntityClass(), self::ANNOTATION);
        if ($field === null) {
            return $event->handle($data);
        }
        if (array_key_exists($field->getName(), $data) && empty($data[$field->getName()])) {
            $data[$field->getName()] = null;
        } else {
            $data[$field->getName()] = $this->getAccountId();
        }
        return $event->handle($data);
    }
}
