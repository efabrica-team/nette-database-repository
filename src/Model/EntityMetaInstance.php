<?php

namespace Efabrica\NetteDatabaseRepository\Model;

use ReflectionClass;

class EntityMetaInstance
{
    /**
     * @var EntityProperty[]
     */
    public array $properties = [];

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

    public function parseProperties(ReflectionClass $refl): void
    {
        $doc = $refl->getDocComment() ?: '';
        if (preg_match_all('/@property\s+(\S+)\s+\$(\S+)\s+([^\n]+)/', $doc, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (isset($match[3])) {
                    $this->properties[$match[2]] = new EntityProperty($match);
                }
            }
        }
    }
}
