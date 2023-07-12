<?php

namespace Efabrica\NetteDatabaseRepository\CodeGen;

use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Repository\RepositoryDependencies;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Parameter;
use Nette\Utils\Strings;
use ReflectionClass;

class RepositoryWriter
{
    private static function createRepositoryBase(EntityStructure $structure): ClassType
    {
        $class = new ClassType($structure->getClassName() . 'RepositoryBase', $structure->repositoryGenNamespace);
        $queryClass = $structure->queryNamespace->getName() . '\\' . $structure->getClassName() . 'Query';
        $entityClass = $structure->entityNamespace->getName() . '\\' . $structure->getClassName();
        $structure->repositoryGenNamespace
            ->addUse($entityClass)
            ->addUse($queryClass)
            ->addUse(Repository::class)
            ->addUse(RepositoryDependencies::class)
        ;
        $class->setExtends(Repository::class);
        $class->addComment('@generated');
        $class->addComment('@method ' . $structure->getClassName() . 'Query query(bool $events = true)');
        $class->addComment("@method {$structure->getClassName()}|null find(string|int|array|{$structure->getClassName()} \$id, bool \$defaultWhere = true)");
        $class->addComment("@method {$structure->getClassName()}      lookup({$structure->getClassName()}|string|int|array \$id)");
        $class->addComment("@method {$structure->getClassName()}|null findOneBy(array \$conditions, bool \$defaultWhere = true)");
        $class->addComment("@method {$structure->getClassName()}Query findBy(array \$conditions)");
        $class->addComment("@method insert({$structure->getClassName()} ...\$entities)");
        $class->addComment("@method update({$structure->getClassName()} ...\$entities)");
        $class->addComment("@method delete({$structure->getClassName()} ...\$entities)");
        $class->addComment("@method class
        -string<{$structure->getClassName()}> getEntityClass()");
        $class->addComment("@method {$structure->getClassName()} createRow(array \$row = [])");

        $class->addMethod('__construct')
            ->setParameters([
                (new Parameter('deps'))->setType(RepositoryDependencies::class),
            ])
            ->setBody("parent::__construct('{$structure->getTableName()}', {$structure->getClassName()}::class, {$structure->getClassName()}Query::class, \$deps);")
        ;

        return $class;
    }

    public static function writeRepositoryBase(EntityStructure $structure): void
    {
        $class = self::createRepositoryBase($structure);
        $namespace = $structure->repositoryGenNamespace;
        $structure->writeClass($class, $namespace, $structure->repositoryGenDir);
    }

    private static function createRepository(EntityStructure $structure): ClassType
    {
        $class = new ClassType($structure->getClassName() . 'Repository', $structure->repositoryNamespace);
        $baseClassName = $structure->repositoryGenNamespace->getName() . '\\' . $structure->getClassName() . 'RepositoryBase';
        $structure->repositoryNamespace->addUse($baseClassName);
        $class->setExtends($baseClassName);
        return $class;
    }

    private static function modifyRepository(EntityStructure $structure): void
    {
        $class = new ReflectionClass($structure->repositoryNamespace->getName() . '\\' . $structure->getClassName() . 'Repository');

        $lines = file($class->getFileName(), FILE_IGNORE_NEW_LINES);
        self::modifyExtends($structure, $class, $lines);
        self::migrateMagicMethods($lines, $structure);

        file_put_contents($class->getFileName(), implode("\n", $lines));
    }

    public static function modifyExtends(EntityStructure $structure, ReflectionClass $class, array &$lines): void
    {
        $shortBaseClassName = $structure->getClassName() . 'RepositoryBase';
        $baseClassName = $structure->repositoryGenNamespace->getName() . '\\' . $shortBaseClassName;
        $queryClassName = $structure->queryNamespace->getName() . '\\' . $structure->getClassName() . 'Query';
        $entityClassName = $structure->entityNamespace->getName() . '\\' . $structure->getClassName();
        $extends = str_contains($lines[$class->getStartLine()], 'extends')
            ? $lines[$class->getStartLine()]
            : $lines[$class->getStartLine() - 1];
        if (!str_contains($extends, 'extends') || str_contains($extends, $shortBaseClassName)) {
            return;
        }
        $lines[$class->getStartLine() - 1] = preg_replace(
            '/ extends\s+\w+/',
            " extends " . $shortBaseClassName, $extends
        );

        $useLine = null;
        $namespaceLine = null;
        foreach ($lines as $i => $line) {
            if (str_starts_with($line, 'use ')) {
                $useLine = $i;
                break;
            }
            if (str_starts_with($line, 'namespace ')) {
                $namespaceLine = $i;
            }
        }
        if ($useLine === null && $namespaceLine === null) {
            return;
        }

        $useText = "\nuse $baseClassName;\nuse $queryClassName;\nuse $entityClassName;";
        if ($useLine !== null) {
            $lines[$useLine] .= $useText;
        } elseif ($namespaceLine !== null) {
            array_splice($lines, $namespaceLine + 1, 0, $useText);
        }
    }

    public static function migrateMagicMethods(array &$lines, EntityStructure $structure): void
    {
        foreach ($lines as $i => $line) {
            if (preg_match('/@method.*\s+find(\w+)\(/', $line, $matches)) {
                unset($lines[$i]);
            } elseif (str_contains($line, ' \$tableName = ')) {
                unset($lines[$i]);
                continue;
            } else {
                continue;
            }
            $methodName = $matches[1];
            $findColumn = Strings::after($methodName, 'By');
            $findColumn = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $findColumn));
            $one = str_starts_with($methodName, 'OneBy');
            $returnType = $one ? $structure->getClassName() : $structure->getClassName() . 'Query';
            $methodCode = [
                "    /**",
                "     * @deprecated",
                "     * @param mixed \$value",
                "     */",
                "    public function find{$methodName}(\$value): $returnType",
                "    {",
                "        return \$this->find" . ($one ? 'One' : '') . "By([{$structure->getClassName()}::$findColumn => \$value]);",
                "    }",
            ];
            $endingLine = null;
            foreach ($lines as $j => $l) {
                if (str_contains($l, '}')) {
                    $endingLine = $j;
                }
            }
            array_splice($lines, $endingLine - 1, 0, $methodCode);
        }
    }

    public static function writeRepository(EntityStructure $structure): void
    {
        $repoClass = $structure->repositoryNamespace->getName() . '\\' . $structure->getClassName() . 'Repository';
        if (class_exists($repoClass)) {
            self::modifyRepository($structure);
        } else {
            $class = self::createRepository($structure);
            $structure->writeClass($class, $class->getNamespace(), $structure->repositoryDir);
        }
    }
}
