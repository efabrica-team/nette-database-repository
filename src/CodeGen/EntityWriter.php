<?php

namespace Efabrica\NetteDatabaseRepository\CodeGen;

use DateTimeInterface;
use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Traits\Cast\CastBehavior;
use Nette\DI\Container;
use Nette\PhpGenerator\ClassType;

class EntityWriter
{
    private static function createBody(EntityStructure $structure): ClassType
    {
        $structure->entityNamespace->addUse($structure->entityGenNamespace->getName() . '\\' . $structure->getClassName());

        $class = new ClassType($structure->getClassName() . 'Body', $structure->entityNamespace);
        $class->setTrait();
        $class->addComment("@mixin {$structure->getClassName()}");

        return $class;
    }

    private static function createClass(EntityStructure $structure, Container $container): ClassType
    {
        $structure->entityGenNamespace->addUse(Entity::class);
        $structure->entityGenNamespace->addUse($structure->entityNamespace->getName() . '\\' . $structure->getClassName() . 'Body');

        $class = new ClassType($structure->getClassName(), $structure->entityGenNamespace);
        $class->setExtends(Entity::class);
        $class->setFinal();
        $class->addComment("@generated Do Not Modify!\n");
        $class->addTrait($structure->entityNamespace->getName() . '\\' . $structure->getClassName() . 'Body');

        /** @var Repository $repo */
        $casts = [];
        if ($container->hasService(ModuleWriter::getRepoServiceName($structure))) {
            $repo = $container->getByName(ModuleWriter::getRepoServiceName($structure));
            foreach ($repo->behaviors()->all() as $behavior) {
                if ($behavior instanceof CastBehavior) {
                    $casts[$behavior->getField()] = $behavior->getCastType();
                }
            }
        }

        foreach ($structure->getProperties() as $prop) {
            $propName = $casts[$prop->getName()] ?? null;
            if (isset($propName)) {
                if (str_contains($prop->getType(), '|null')) {
                    $propName .= '|null';
                }
                $prop->setType($propName);
            }
            $class->addComment($prop->toString());
            if (\str_contains($prop->getType(), DateTimeInterface::class)) {
                $structure->entityGenNamespace->addUse(DateTimeInterface::class);
            }
            $class->addConstant($prop->getName(), $prop->getName())->setPublic();
        }
        return $class;
    }

    public static function writeBody(EntityStructure $structure): void
    {
        if (!class_exists($structure->entityNamespace->getName() . '\\' . $structure->getClassName() . 'Body')) {
            $structure->writeClass(self::createBody($structure), $structure->entityGenDir);
        }
    }

    public static function writeEntity(EntityStructure $structure, Container $container): void
    {
        $entityClassName = "{$structure->entityNamespace->getName()}\\{$structure->getClassName()}";
        if (!class_exists($entityClassName)) {
            $entity = self::createClass($structure, $container);
            $structure->writeClass($entity, $structure->entityGenDir);
        }
    }
}
