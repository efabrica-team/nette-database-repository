<?php

namespace Efabrica\NetteRepository\CodeGen;

use Efabrica\NetteRepository\Repository\Query;
use Nette\PhpGenerator\ClassType;

class QueryWriter
{
    public static function writeAppQueryBase(EntityStructure $structure, FileWriter $writer): void
    {
        $class = new ClassType('QueryBase', $structure->queryNamespace);
        if (class_exists($structure->queryNamespace->getName() . '\\' . $class->getName())) {
            return;
        }

        $structure->queryNamespace->addUse(Query::class);
        $class->setAbstract();
        $class->setExtends(Query::class);

        $writer->writeClass($class, $structure->queryDir);
        $structure->repositoryNamespace->removeUse(Query::class);
    }

    public static function writeQueryBase(EntityStructure $structure, FileWriter $writer): void
    {
        $repositoryClass = $structure->repositoryNamespace->getName() . '\\' . $structure->getClassName() . 'Repository';
        $entityClass = $structure->entityGenNamespace->getName() . '\\' . $structure->getClassName();
        $queryBaseClass = $structure->queryNamespace->getName() . '\\QueryBase';
        $structure->queryGenNamespace
            ->addUse($entityClass)
            ->addUse($repositoryClass)
            ->addUse($queryBaseClass)
        ;
        $baseClass = new ClassType("{$structure->getClassName()}QueryBase", $structure->queryGenNamespace);
        $baseClass->setAbstract();
        $baseClass->setExtends($queryBaseClass);

        $baseClass->addComment('@internal Typehint extended classes only');
        $baseClass->addComment('@generated');
        $baseClass->addComment("@method insert({$structure->getClassName()}|array \$data)");
        $baseClass->addComment("@method {$structure->getClassName()}[] fetchAll()");
        $baseClass->addComment("@method {$structure->getClassName()}|null fetch()");
        $baseClass->addComment("@method {$structure->getClassName()}|null first()");
        $baseClass->addComment("@method {$structure->getClassName()} createRow(array \$data = [])");
        $baseClass->addComment("@method {$structure->getClassName()} current()");
        $baseClass->addComment("@method {$structure->getClassName()}Repository getRepository()");

        $writer->writeClass($baseClass, $structure->queryGenDir);
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

    public static function writeQuery(EntityStructure $structure, FileWriter $writer): void
    {
        $queryClass = $structure->queryNamespace->getName() . '\\' . $structure->getClassName() . 'Query';
        if (class_exists($queryClass)) {
            return;
        }
        $writer->writeClass(self::createQuery($structure), $structure->queryDir);
    }
}
