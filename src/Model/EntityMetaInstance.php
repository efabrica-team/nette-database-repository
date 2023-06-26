<?php

namespace Efabrica\NetteDatabaseRepository\Model;

use ReflectionClass;

class EntityMetaInstance
{
    private const PROP_REGEX = '/@property +([\w|]+) +\$(\w+) *(?:\(([^\)]+)\))? *([^\n]*)/';
    /**
     * @var EntityProperty[]
     */
    public array $properties = [];

    private array $annotationCache = [];

    /**
     * @param class-string<Entity> $class
     */
    public function __construct(string $class)
    {
        $refl = new ReflectionClass($class);
        do {
            $this->parseProperties($refl);
        } while (($refl = $refl->getParentClass()) && $refl->getName() !== Entity::class);
    }

    private function parseProperties(ReflectionClass $refl): void
    {
        $doc = $refl->getDocComment() ?: '';
        if (preg_match_all(self::PROP_REGEX, $doc, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (isset($match[3])) {
                    $this->properties[$match[2]] = new EntityProperty(...$match);
                }
            }
        }
    }

    public function getPropertiesWithAnnotation(string $annotation): array
    {
        if (isset($this->annotationCache[$annotation])) {
            return $this->annotationCache[$annotation];
        }
        $result = [];
        foreach ($this->properties as $property) {
            if ($property->hasAnnotation($annotation)) {
                $result[] = $property;
            }
        }
        return $this->annotationCache[$annotation] = $result;
    }
}
