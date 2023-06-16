<?php

namespace Efabrica\NetteDatabaseRepository\Model;

use WeakMap;

class EntityManager
{
    private static WeakMap $originalMap;

    private static function getOriginalMap(): WeakMap
    {
        return self::$originalMap ??= new WeakMap();
    }

    public static function saveOriginal(Entity $entity): void
    {
        self::getOriginalMap()->offsetSet($entity, $entity->toArray());
    }

    public static function getOriginal(Entity $entity): array
    {
        return self::getOriginalMap()->offsetExists($entity) ? self::getOriginalMap()->offsetGet($entity) : [];
    }
}
