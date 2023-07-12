<?php

namespace Efabrica\NetteDatabaseRepository\CodeGen;

use Efabrica\NetteDatabaseRepository\Model\EntityMetaInstance;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Strings;
use Efabrica\NetteDatabaseRepository\Model\Entity;

class EntityWriter
{
    private static function createClass(EntityStructure $structure): ClassType
    {
        $entityClass = new ClassType($structure->getClassName(), $structure->entityNamespace);
        $entityClass->setExtends(Entity::class);
        foreach ($structure->getProperties() as $prop) {
            $entityClass->addComment($prop->toString());
            $entityClass->addConstant($prop->getName(), $prop->getName())->setPublic();
        }
        return $entityClass;
    }

    private static function modifyClass(EntityStructure $structure): ClassType
    {
        $entityClassName = "{$structure->entityNamespace->getName()}\\{$structure->getClassName()}";
        $entityClass = ClassType::from($entityClassName, true);
        $entityClass->getNamespace()->addUse(Entity::class);
        $entityClass->setExtends(Entity::class);
        self::updateDocComments($entityClassName, $entityClass, $structure);
        self::updateConstants($structure, $entityClassName, $entityClass);

        return $entityClass;
    }

    public static function writeEntity(EntityStructure $structure): void
    {
        $structure->entityNamespace->addUse(Entity::class);
        $entityClassName = "{$structure->entityNamespace->getName()}\\{$structure->getClassName()}";
        if (class_exists($entityClassName)) {
            $entity = self::modifyClass($structure);
        } else {
            $entity = self::createClass($structure);
        }
        $structure->writeClass($entity, $structure->entityNamespace, $structure->entityDir);
    }

    public static function updateDocComments(string $entityClassName, ClassType $entityClass, EntityStructure $structure): array
    {
        $meta = new EntityMetaInstance($entityClassName);
        $comments = explode("\n", $entityClass->getComment());
        $props = $structure->getProperties();
        foreach ($comments as $i => $comment) {
            if (!Strings::startsWith($comment, '@property')) {
                continue;
            }
            foreach ($props as $property) {
                if (Strings::contains($comment, $property->getName())) {
                    $metaProp = $meta->properties[$property->getName()];
                    $comments[$i] = $property->toString($metaProp->getAnnotations());
                }
            }
        }
        $entityClass->setComment(implode("\n", $comments));
        $comment = $entityClass->getComment();
        foreach ($props as $property) {
            if (!str_contains($comment, '$' . $property->getName() . ' ')) {
                $entityClass->addComment($property->toString());
            }
        }
        return [$props, $property];
    }

    public static function updateConstants(EntityStructure $structure, string $entityClassName, ClassType $entityClass): void
    {
        $props = $structure->getProperties();
        $constants = $entityClass->getConstants();
        foreach ($constants as $constant) {
            if ($constant->isPublic() && $constant->getComment() === null && !isset($props[$constant->getName()])) {
                $entityClass->removeConstant($constant->getName());
            }
        }
        foreach ($props as $property) {
            if (!isset($constants[$property->getName()])) {
                $entityClass->addConstant($property->getName(), $property->getName())->setPublic();
            }
        }
    }
}
