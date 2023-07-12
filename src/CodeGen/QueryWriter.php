<?php

namespace Efabrica\NetteDatabaseRepository\CodeGen;

use DateTimeInterface;
use Efabrica\NetteDatabaseRepository\Model\EntityProperty;
use Efabrica\NetteDatabaseRepository\Repository\Query;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use RuntimeException;

class QueryWriter
{
    public static function createBaseQuery(EntityStructure $structure): ClassType
    {
        $repositoryClass = $structure->repositoryNamespace->getName() . '\\' . $structure->getClassName() . 'Repository';
        $entityClass = $structure->entityNamespace->getName() . '\\' . $structure->getClassName();
        $structure->queryGenNamespace
            ->addUse($entityClass)
            ->addUse($repositoryClass)
            ->addUse(Query::class)
        ;
        $baseClass = new ClassType("{$structure->getClassName()}QueryBase", $structure->queryGenNamespace);
        $baseClass->addComment('@generated');
        $baseClass->addComment("@method insert({$structure->getClassName()}|{$structure->getClassName()}[] \$data)");
        $baseClass->addComment("@method {$structure->getClassName()}[] fetchAll()");
        $baseClass->addComment("@method {$structure->getClassName()}|null fetch()");
        $baseClass->addComment("@method {$structure->getClassName()} createRow(array \$data = [])");
        $baseClass->addComment("@method {$structure->getClassName()}Repository getRepository()");
        $baseClass->setExtends(Query::class);

        $constructor = $baseClass->addMethod('__construct')
            ->setBody("parent::__construct(\$repository, \$events);")
        ;
        $constructor->addParameter('repository')->setType($repositoryClass);
        $constructor->addParameter('events')->setType('bool')->setDefaultValue(true);

        return $baseClass;
    }

    public static function writeQueryBase(EntityStructure $structure): void
    {
        $structure->writeClass(self::createBaseQuery($structure), $structure->queryGenNamespace, $structure->queryGenDir);
    }

    private static function createQuery(EntityStructure $structure): ClassType
    {
        $class = new ClassType($structure->getClassName() . 'Query', $structure->queryNamespace);
        $baseClassName = $structure->queryGenNamespace->getName() . '\\' . $structure->getClassName() . 'QueryBase';
        $structure->queryNamespace->addUse($baseClassName);
        $class->setExtends($baseClassName);
        return $class;
    }

    private static function modifyQuery(EntityStructure $structure): ClassType
    {
        $class = ClassType::from($structure->queryNamespace->getName() . '\\' . $structure->getClassName() . 'Query', true);
        $baseClassName = $structure->queryGenNamespace->getName() . '\\' . $structure->getClassName() . 'QueryBase';
        $class->getNamespace()->addUse($baseClassName);
        $class->setExtends($baseClassName);
        return $class;
    }

    public static function writeQuery(EntityStructure $structure): void
    {
        $repoClass = $structure->queryNamespace->getName() . '\\' . $structure->getClassName() . 'Query';
        if (class_exists($repoClass)) {
            $class = self::modifyQuery($structure);
        } else {
            $class = self::createQuery($structure);
        }
        $namespace = $class->getNamespace();
        $structure->writeClass($class, $namespace, $structure->queryDir);
    }
}
