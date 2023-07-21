<?php

namespace Efabrica\NetteDatabaseRepository\Traits\AES;

use Efabrica\NetteDatabaseRepository\Event\InsertEventResponse;
use Efabrica\NetteDatabaseRepository\Event\InsertRepositoryEvent;
use Efabrica\NetteDatabaseRepository\Event\SelectQueryEvent;
use Efabrica\NetteDatabaseRepository\Event\SelectQueryResponse;
use Efabrica\NetteDatabaseRepository\Event\UpdateQueryEvent;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Subscriber\EventSubscriber;
use Nette\Database\Table\ActiveRow;

class AESEventSubscriber extends EventSubscriber
{
    public function supportsRepository(Repository $repository): bool
    {
        return $repository->behaviors()->has(AESBehavior::class);
    }

    public function onSelect(SelectQueryEvent $event): SelectQueryResponse
    {
        /** @var AESBehavior $behavior */
        $behavior = $event->getBehaviors()->get(AESBehavior::class);
        $repository = $event->getRepository();
        $selectParts = [$repository->getTableName() . '.*'];
        foreach ($behavior->encryptedFields() as $field) {
            $selectParts[] = $this->convertEncryptedField($repository, $behavior, $field) . ' AS ' . $field;
        }
        $event->getQuery()->select($selectParts);
        return $event->handle();
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        /** @var AESBehavior $behavior */
        $behavior = $event->getBehaviors()->get(AESBehavior::class);
        $repository = $event->getRepository();
        foreach ($event->getEntities() as $entity) {
            foreach ($behavior->encryptedFields() as $field) {
                $entity[$field] = $this->encryptValue($repository, $behavior, $entity[$field]);
            }
        }
        return $event->handle();
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        /** @var AESBehavior $behavior */
        $behavior = $event->getBehaviors()->get(AESBehavior::class);
        $repository = $event->getRepository();
        foreach ($behavior->encryptedFields() as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->encryptValue($repository, $behavior, $data[$field]);
            }
        }
        return $event->handle($data);
    }

    protected function convertEncryptedField(Repository $repository, AESBehavior $behavior, string $field): string
    {
        if ($this->ivFunction($repository)) {
            return 'CONVERT(AES_DECRYPT(UNHEX(SUBSTRING(' . $repository->getTableName() . '.' . $field . ', 33)), ' . $behavior->keyFunction() . ', UNHEX(SUBSTRING(' . $repository->getTableName() . '.' . $field . ', 1, 32))) USING utf8)';
        }
        return 'CONVERT(AES_DECRYPT(UNHEX(' . $repository->getTableName() . '.' . $field . '), ' . $behavior->keyFunction() . $this->ivFunction($repository) . ') USING utf8)';
    }

    protected function encryptValue(Repository $repository, AESBehavior $behavior, string $value): string
    {
        $ivFunction = $this->ivFunction($repository);
        if ($ivFunction) {
            /** @var literal-string $ivFunction */
            /** @var ActiveRow $initVector */
            $initVector = $repository->getExplorer()->fetch($ivFunction);
            $randomBytes = addslashes($initVector->random);

            /** @var literal-string $queryString */
            $queryString = 'SELECT HEX(CONCAT("' . $randomBytes . '", AES_ENCRYPT("' . addslashes($value) . '", ' . $behavior->keyFunction() . ', "' . $randomBytes . '"))) AS encrypted';
            /** @var  ActiveRow $row */
            $row = $repository->getExplorer()->fetch($queryString);
            return $row['encrypted'];
        }

        /** @var literal-string $queryString */
        $queryString = 'SELECT HEX(AES_ENCRYPT("' . addslashes($value) . '", ' . $behavior->keyFunction() . $this->ivFunction($repository) . ')) AS encrypted';
        /** @var ActiveRow $row */
        $row = $repository->getExplorer()->fetch($queryString);
        return $row['encrypted'];
    }

    private function ivFunction(Repository $repository): string
    {
        $blockEncryptionMode = $repository->getExplorer()->fetch('SHOW variables WHERE Variable_name = "block_encryption_mode"');
        $blockEncryptionModeValue = $blockEncryptionMode ? $blockEncryptionMode->Value : 'aes-128-ecb';

        if (str_contains($blockEncryptionModeValue, '-cbc')) {
            return 'SELECT RANDOM_BYTES(16) AS random';
        }
        return '';
    }
}
