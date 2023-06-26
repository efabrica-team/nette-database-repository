<?php

namespace Efabrica\NetteDatabaseRepository\CodeGen;

use Efabrica\NetteDatabaseRepository\Model\Entity;
use Efabrica\NetteDatabaseRepository\Repository\Repository;
use Efabrica\NetteDatabaseRepository\Repository\RepositoryDependencies;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Parameter;
use RuntimeException;

class RepositoryWriter
{
    private static function createRepositoryBase(EntityStructure $structure): ClassType
    {
        $class = new ClassType($structure->getClassName() . 'RepositoryBase', $structure->getGeneratedNamespace());
        $structure->getGeneratedNamespace()
            ->addUse($structure->getEntityNamespace()->getName() . '\\' . $structure->getClassName())
            ->addUse($structure->getRepositoryNamespace()->getName() . '\\' . $structure->getClassName() . 'Repository')
            ->addUse(Repository::class)
            ->addUse(RepositoryDependencies::class)
        ;
        $class->setExtends(Repository::class);
        $class->addComment('@generated');
        $class->addComment('@method ' . $structure->getClassName() . 'Query query(bool $events = true)');
        $class->addComment("@method {$structure->getClassName()}|null find(string|int|array|{$structure->getClassName()} \$id)");
        $class->addComment("@method {$structure->getClassName()}      lookup({$structure->getClassName()}|string|int|array \$id)");
        $class->addComment("@method {$structure->getClassName()}|null findOneBy(array \$conditions, bool \$events = true)");
        $class->addComment("@method {$structure->getClassName()}Query findBy(array \$conditions)");
        $class->addComment("@method insert({$structure->getClassName()} ...\$entities)");
        $class->addComment("@method update({$structure->getClassName()} ...\$entities)");
        $class->addComment("@method delete({$structure->getClassName()} ...\$entities)");
        $class->addComment("@method class-string<{$structure->getClassName()}> getEntityClass()");
        $class->addComment("@method {$structure->getClassName()} createRow(array \$row = [])");

        $class->addMethod('__construct')
            ->setParameters([
                (new Parameter('deps'))->setType('RepositoryDependencies'),
            ])
            ->setBody("parent::__construct('{$structure->getTableName()}', {$structure->getClassName()}::class, {$structure->getClassName()}Query::class, \$deps);")
        ;
        return $class;
    }

    public static function writeRepositoryBase(EntityStructure $structure, string $appDir): void
    {
        $class = self::createRepositoryBase($structure);
        $namespace = $structure->getGeneratedNamespace();
        $filename = "$appDir/modules/Core/Repository/Generated/{$class->getName()}.php";
        if (!@mkdir(dirname($filename), 0777, true) && !is_dir(dirname($filename))) {
            throw new RuntimeException("Cannot create directory $filename");
        }
        file_put_contents(
            $filename,
            "<?php\n\n" . $namespace . $class
        );
    }

    private static function createRepository(EntityStructure $structure): ClassType
    {
        $class = new ClassType($structure->getClassName() . 'Repository', $structure->getRepositoryNamespace());
        $baseClassName = $structure->getGeneratedNamespace()->getName() . '\\' . $structure->getClassName() . 'RepositoryBase';
        $structure->getRepositoryNamespace()->addUse($baseClassName);
        $class->setExtends($baseClassName);
        return $class;
    }

    private static function modifyRepository(EntityStructure $structure): ClassType
    {
        $class = ClassType::from($structure->getRepositoryNamespace()->getName() . '\\' . $structure->getClassName() . 'Repository', true);
        $baseClassName = $structure->getGeneratedNamespace()->getName() . '\\' . $structure->getClassName() . 'RepositoryBase';
        $class->getNamespace()->addUse($baseClassName);
        $class->setExtends($baseClassName);
        return $class;
    }

    public static function writeRepository(EntityStructure $structure, string $appDir): void
    {
        $repoClass = $structure->getRepositoryNamespace()->getName() . '\\' . $structure->getClassName() . 'Repository';
        if (class_exists($repoClass)) {
            $class = self::modifyRepository($structure);
        } else {
            $class = self::createRepository($structure);
        }
        $namespace = $class->getNamespace();
        $structure->writeClass($class, $namespace, "$appDir/modules/Core/Repository");
    }
}
