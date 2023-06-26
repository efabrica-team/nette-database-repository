<?php

namespace Efabrica\NetteDatabaseRepository\Model;

use Generator;

/**
 * This class is used to store cached metadata about entities and use them to modify entities.
 */
class EntityMeta
{
    /**
     * @var EntityMetaInstance[]
     */
    private static array $meta = [];

    /**
     * @param class-string<Entity> $class
     */
    public static function getMeta(string $class): EntityMetaInstance
    {
        return self::$meta[$class] ??= new EntityMetaInstance($class);
    }

    /**
     * @param class-string<Entity> $class
     */
    public static function getProperty(string $class, string $name): ?EntityProperty
    {
        return self::getMeta($class)->properties[$name] ?? null;
    }

    /**
     * @param class-string<Entity> $class
     * @return EntityProperty[]
     */
    public static function getProperties(string $class): array
    {
        return self::getMeta($class)->properties;
    }

    /**
     * @param class-string<Entity> $class
     */
    public static function getAnnotatedProperty(string $class, string $annotation): ?EntityProperty
    {
        foreach (self::getAnnotatedProperties($class, $annotation) as $property) {
            return $property;
        }
        return null;
    }

    /**
     * @param class-string<Entity> $class
     * @return Generator<EntityProperty>
     */
    public static function getAnnotatedProperties(string $class, string $annotation): iterable
    {
        foreach (self::getMeta($class)->getPropertiesWithAnnotation($annotation) as $property) {
            yield $property;
        }
    }
}
