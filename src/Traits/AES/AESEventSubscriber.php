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
        return $repository instanceof AESRepository;
    }

    public function onSelect(SelectQueryEvent $event): SelectQueryResponse
    {
        /** @var AESRepository&Repository $repository */
        $repository = $event->getRepository();
        $selectParts = [$repository->getTableName() . '.*'];
        foreach ($repository->encryptedFields() as $field) {
            $selectParts[] = $this->convertEncryptedField($repository, $field) . ' AS ' . $field;
        }
        $event->getQuery()->select($selectParts);
        return $event->handle();
    }

    public function onInsert(InsertRepositoryEvent $event): InsertEventResponse
    {
        /** @var Repository&AESRepository $repository */
        $repository = $event->getRepository();
        foreach ($event->getEntities() as $entity) {
            foreach ($repository->encryptedFields() as $field) {
                $entity[$field] = $this->encryptValue($repository, $entity[$field]);
            }
        }
        return $event->handle();
    }

    public function onUpdate(UpdateQueryEvent $event, array &$data): int
    {
        /** @var AESRepository&Repository $repository */
        $repository = $event->getRepository();
        foreach ($repository->encryptedFields() as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->encryptValue($repository, $data[$field]);
            }
        }
        return $event->handle($data);
    }

    protected function convertEncryptedField(Repository $repository, string $field): string
    {
        /** @var Repository&AESRepository $repository */
        if ($this->ivFunction($repository)) {
            return 'CONVERT(AES_DECRYPT(UNHEX(SUBSTRING(' . $repository->getTableName() . '.' . $field . ', 33)), ' . $repository->keyFunction() . ', UNHEX(SUBSTRING(' . $repository->getTableName() . '.' . $field . ', 1, 32))) USING utf8)';
        }
        return 'CONVERT(AES_DECRYPT(UNHEX(' . $repository->getTableName() . '.' . $field . '), ' . $repository->keyFunction() . $this->ivFunction($repository) . ') USING utf8)';
    }

    protected function encryptValue(Repository $repository, string $value): string
    {
        /** @var Repository&AESRepository $repository */
        $ivFunction = $this->ivFunction($repository);
        if ($ivFunction) {
            /** @var literal-string $ivFunction */
            /** @var ActiveRow $initVector */
            $initVector = $repository->getExplorer()->fetch($ivFunction);
            $randomBytes = addslashes($initVector->random);

            /** @var literal-string $queryString */
            $queryString = 'SELECT HEX(CONCAT("' . $randomBytes . '", AES_ENCRYPT("' . addslashes($value) . '", ' . $repository->keyFunction() . ', "' . $randomBytes . '"))) AS encrypted';
            /** @var  ActiveRow $row */
            $row = $repository->getExplorer()->fetch($queryString);
            return $row['encrypted'];
        }

        /** @var literal-string $queryString */
        $queryString = 'SELECT HEX(AES_ENCRYPT("' . addslashes($value) . '", ' . $repository->keyFunction() . $this->ivFunction($repository) . ')) AS encrypted';
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
