<?php

namespace Efabrica\NetteRepository\CodeGen;

use Efabrica\NetteRepository\Repository\Query;
use Nette\PhpGenerator\ClassType;

class QueryWriter
{
    public static function createQueryBase(EntityStructure $structure): ClassType
    {
        $repositoryClass = $structure->repositoryNamespace->getName() . '\\' . $structure->getClassName() . 'Repository';
        $entityClass = $structure->entityGenNamespace->getName() . '\\' . $structure->getClassName();
        $structure->queryGenNamespace
            ->addUse($entityClass)
            ->addUse($repositoryClass)
            ->addUse(Query::class)
        ;
        $baseClass = new ClassType("{$structure->getClassName()}QueryBase", $structure->queryGenNamespace);
        $baseClass->setAbstract();
        $baseClass->addComment('@internal Typehint extended classes only');
        $baseClass->addComment('@generated');
        $baseClass->addComment("@method insert({$structure->getClassName()}|array \$data)");
        $baseClass->addComment("@method {$structure->getClassName()}[] fetchAll()");
        $baseClass->addComment("@method {$structure->getClassName()}|null fetch()");
        $baseClass->addComment("@method {$structure->getClassName()} createRow(array \$data = [])");
        $baseClass->addComment("@method {$structure->getClassName()} current()");
        $baseClass->addComment("@method {$structure->getClassName()}Repository getRepository()");
        $baseClass->setExtends(Query::class);

        return $baseClass;
    }

    public static function writeQueryBase(EntityStructure $structure): void
    {
        $structure->writeClass(self::createQueryBase($structure), $structure->queryGenDir);
    }

    private static function createQuery(EntityStructure $structure): ClassType
    {
        $queryGenClass = $structure->queryGenNamespace->getName() . '\\' . $structure->getClassName() . 'QueryBase';
        $structure->queryNamespace->addUse($structure->entityGenNamespace->getName() . '\\' . $structure->getClassName());
        $structure->queryNamespace->addUse($queryGenClass);

        $class = new ClassType($structure->getClassName() . 'Query', $structure->queryNamespace);
        $class->setFinal();
        $class->setExtends($queryGenClass);

        return $class;
    }

    public static function writeQuery(EntityStructure $structure): void
    {
        $queryClass = $structure->queryNamespace->getName() . '\\' . $structure->getClassName() . 'Query';
        if (class_exists($queryClass)) {
            return;
        }
        $structure->writeClass(self::createQuery($structure), $structure->queryDir);
    }
}
