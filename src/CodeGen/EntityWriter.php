<?php

namespace Efabrica\NetteRepository\CodeGen;

use DateTimeInterface;
use Efabrica\NetteRepository\Model\Entity;
use Nette\PhpGenerator\ClassType;
use function str_contains;

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

    private static function createClass(EntityStructure $structure, array $structures): ClassType
    {
        $structure->entityGenNamespace->addUse(Entity::class);
        $structure->entityGenNamespace->addUse($structure->entityNamespace->getName() . '\\' . $structure->getClassName() . 'Body');

        $class = new ClassType($structure->getClassName(), $structure->entityGenNamespace);
        $class->setExtends(Entity::class);
        $class->setFinal();
        $class->addComment("@generated Do Not Modify!\n");
        $class->addTrait($structure->entityNamespace->getName() . '\\' . $structure->getClassName() . 'Body');

        foreach ($structure->getProperties() as $prop) {
            $propName = $casts[$prop->getName()] ?? null;
            if (isset($propName)) {
                if (str_contains($prop->getType(), '|null')) {
                    $propName .= '|null';
                }
                $prop->setType($propName);
            }
            $class->addComment($prop->toString());
            if (str_contains($prop->getType(), DateTimeInterface::class)) {
                $structure->entityGenNamespace->addUse(DateTimeInterface::class);
            }
            $class->addConstant($prop->getName(), $prop->getName())->setPublic();
        }

        foreach ($structure->toOne as $column => $relation) {
            [$relatedTable, $relatedColumn] = $relation;
            /** @var EntityStructure $relatedStructure */
            $relatedStructure = $structures[$relatedTable];
            $className = $relatedStructure->getClassName();
            $relatedEntity = $relatedStructure->entityNamespace->getName() . '\\' . $className;
            $relatedRepository = $relatedStructure->repositoryNamespace->getName() . '\\' . $className . 'Repository';
            $structure->entityGenNamespace->addUse($relatedEntity);
            $structure->entityGenNamespace->addUse($relatedRepository);
            $class->addMethod('get' . $structure->toClassName($column))
                ->setBody("return \$this->query({$className}Repository::class, \$events)\n" .
                    "->where('$relatedColumn', \$this[$column])\n" .
                    '->limit(1)->fetch();')
                ->setReturnType($relatedEntity)
                ->setReturnNullable()
                ->addParameter('events')->setType('bool')->setDefaultValue(true)
            ;
        }
        foreach ($structure->toMany as $column => $relation) {
            [$relatedTable, $relatedColumn] = $relation;
            /** @var EntityStructure $relatedStructure */
            $relatedStructure = $structures[$relatedTable];
            $className = $relatedStructure->getClassName();
            $relatedEntity = $relatedStructure->entityNamespace->getName() . '\\' . $className;
            $relatedQuery = $relatedStructure->queryNamespace->getName() . '\\' . $className . 'Query';
            $relatedRepository = $relatedStructure->repositoryNamespace->getName() . '\\' . $className . 'Repository';
            $structure->entityGenNamespace->addUse($relatedEntity);
            $structure->entityGenNamespace->addUse($relatedQuery);
            $structure->entityGenNamespace->addUse($relatedRepository);
            $class->addMethod('get' . $structure->toPluralName($column))
                ->setBody("return \$this->query({$className}Repository::class, \$events)\n" .
                    "->where('$relatedColumn', \$this[$column]);")
                ->setReturnType($relatedQuery)
                ->addComment("@return iterable<{$className}>&{$className}Query")
                ->addParameter('events')->setType('bool')->setDefaultValue(true)
            ;
        }

        return $class;
    }

    public static function writeBody(EntityStructure $structure): void
    {
        if (!class_exists($structure->entityNamespace->getName() . '\\' . $structure->getClassName() . 'Body')) {
            $structure->writeClass(self::createBody($structure), $structure->entityGenDir);
        }
    }

    public static function writeEntity(EntityStructure $structure, array $structures): void
    {
        $entityClassName = "{$structure->entityNamespace->getName()}\\{$structure->getClassName()}";
        if (!class_exists($entityClassName)) {
            $entity = self::createClass($structure, $structures);
            $structure->writeClass($entity, $structure->entityGenDir);
        }
    }
}
