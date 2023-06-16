<?php

namespace Efabrica\NetteDatabaseRepository\Model;

use Generator;
use InvalidArgumentException;

/**
 * This class is used to store cached metadata about entities and use them to modify entities.
 */
class EntityMeta
{
    /**
     * @var EntityMetaInstance[]
     */
    private static array $meta = [];

    public static function getMeta(Entity $entity): EntityMetaInstance
    {
        $class = get_class($entity);
        return self::$meta[$class] ??= new EntityMetaInstance($class);
    }

    public static function toIterable(Entity $entity): Generator
    {
        foreach (self::getMeta($entity)->properties as $name => $property) {
            yield $name => $property->getValue($entity);
        }
    }

    public static function toArray(Entity $entity): array
    {
        $result = [];
        foreach (self::getMeta($entity)->properties as $name => $property) {
            $result[$name] = $property->getValue($entity);
        }
        return $result;
    }

    public static function isSet(Entity $entity, string $key): bool
    {
        return isset(self::getMeta($entity)->properties[$key]);
    }

    /**
     * Does not call any setters. Used to populate directly from database.
     * @param Entity   $entity
     * @param iterable $data
     * @return void
     */
    public static function populate(Entity $entity, iterable $data): void
    {
        $props = self::getMeta($entity)->properties;
        foreach ($data as $columnName => $value) {
            if (isset($props[$columnName])) {
                $props[$columnName]->setValue($entity, $value);
            }
        }
    }

    /**
     * Calls the getter for the given column.
     * @return mixed|null
     */
    public static function get(Entity $entity, string $columnName)
    {
        $getters = self::getMeta($entity)->getters;
        if (isset($getters[$columnName])) {
            $method = $getters[$columnName];
            return $entity->$method();
        }
        throw new InvalidArgumentException("Could not find getter for $columnName in " . get_class($entity));
    }

    /**
     * Calls the setter for the given column.
     * @param mixed $value
     */
    public static function set(Entity $entity, string $columnName, $value): void
    {
        $setters = self::getMeta($entity)->setters;
        if (isset($setters[$columnName])) {
            $method = $setters[$columnName];
            $entity->$method($value);
            return;
        }
        throw new InvalidArgumentException("Could not find setter for $columnName in " . get_class($entity));
    }
}
