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

    abstract public function getHookIgnores(): array;

    /**
     * @return static
     */
    abstract public function importHookIgnores(array $hookIgnores);

    /**
     * @return static
     */
    abstract public function resetHookIgnores();

    /**
     * @return static
     */
    abstract public function ignoreHook(string $hookName);

    /**
     * @return static
     */
    abstract public function ignoreHookType(string $hookType, string $hookName = null);

    /**
     * @return static
     */
    abstract public function ignoreBehavior(?string $traitName, string $hookType = null, string $hookName = null);

    /**
     * @return static
     */
    abstract public function ignoreHooks();
}
