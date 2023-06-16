<?php

namespace Efabrica\NetteDatabaseRepository\Subscriber;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class AnnotationReader
{
    private array $propCache = [];

    private array $methodCache = [];

    public function findProperty(string $class, string $annotation): ?ReflectionProperty
    {
        if (array_key_exists($annotation, $this->propCache[$class] ?? [])) {
            return $this->propCache[$class][$annotation];
        }

        $refl = new ReflectionClass($class);
        foreach ($refl->getProperties() as $prop) {
            if (str_contains($prop->getDocComment(), $annotation)) {
                return $this->propCache[$class][$annotation] = $prop;
            }
        }

        return $this->propCache[$class][$annotation] = null;
    }

    public function findMethod(string $class, string $annotation): ?ReflectionMethod
    {
        if (array_key_exists($annotation, $this->propCache[$class] ?? [])) {
            return $this->methodCache[$class][$annotation];
        }

        $refl = new ReflectionClass($class);
        foreach ($refl->getMethods() as $method) {
            if (str_contains($method->getDocComment(), $annotation)) {
                $method->setAccessible(true);
                return $this->methodCache[$class][$annotation] = $method;
            }
        }

        return $this->methodCache[$class][$annotation] = null;
    }
}
