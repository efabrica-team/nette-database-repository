<?php

namespace Efabrica\NetteDatabaseRepository\Behaviors;

trait ModelBehavior
{
    abstract public function getTableName(): string;

    abstract public function getData(): array;

    abstract public function toArray(): array;

    abstract public function update(iterable $data): bool;

    abstract public function delete(): int;

    abstract public function castDataToGet(array $data): array;

    abstract public function castDataToSet(array $data): array;
}
