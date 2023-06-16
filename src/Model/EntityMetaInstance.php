<?php

namespace Efabrica\NetteDatabaseRepository\Model;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

class EntityMetaInstance
{
    /**
     * @var ReflectionProperty[]
     */
    public array $properties = [];

    /**
     * @var string[]
     */
    public array $getters = [];

    /**
     * @var string[]
     */
    public array $setters = [];

    /**
     * @param class-string<Entity> $class
     */
    public function __construct(string $class)
    {
        $refl = new ReflectionClass($class);
        foreach ($refl->getProperties(ReflectionProperty::IS_PRIVATE) as $property) {
            $property->setAccessible(true);
            $this->properties[$property->getName()] = $property;
        }
        foreach ($refl->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();
            if (str_starts_with($name, 'get')) {
                $this->getters[self::toSnakeCase(substr($name, 3))] = $name;
            } elseif (str_starts_with($name, 'is')) {
                $this->getters[self::toSnakeCase(substr($name, 2))] = $name;
            } elseif (str_starts_with($name, 'has')) {
                $this->getters[self::toSnakeCase(substr($name, 3))] = $name;
            } elseif (str_starts_with($name, 'set')) {
                $this->setters[self::toSnakeCase(substr($name, 3))] = $name;
            }
        }
    }

    private static function toSnakeCase(string $camelCase): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $camelCase));
    }
}
