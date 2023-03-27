<?php

namespace Efabrica\NetteDatabaseRepository\Behaviors;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Nette\Utils\DateTime;
use Ramsey\Uuid\Uuid;

trait UuidBehavior
{
    use RepositoryBehavior;

    protected function uuidField(): string
    {
        return 'id';
    }

    final public function beforeInsertGenerateUuid(array $data): array
    {
        if (!isset($data[$this->uuidField()])) {
            $data[$this->uuidField()] = Uuid::uuid4()->toString();
        }
        return $data;
    }
}
