<?php

namespace Efabrica\NetteDatabaseRepository\CodeGen;

use DateTimeInterface;
use Efabrica\NetteDatabaseRepository\Model\EntityProperty;
use Efabrica\NetteDatabaseRepository\Repository\Query;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Printer;
use RuntimeException;

class QueryWriter
{
    public static function createBaseQuery(EntityStructure $structure): ClassType
    {
        $repositoryClass = $structure->getRepositoryNamespace()->getName() . '\\' . $structure->getClassName() . 'Repository';
        $entityClass = $structure->getEntityNamespace()->getName() . '\\' . $structure->getClassName();
        $structure->getGeneratedNamespace()
            ->addUse($entityClass)
            ->addUse($repositoryClass)
            ->addUse(Query::class)
        ;
        $baseClass = new ClassType("{$structure->getClassName()}QueryBase", $structure->getGeneratedNamespace());
        $baseClass->addComment('@generated');
        $baseClass->addComment("@method insert({$structure->getClassName()}|{$structure->getClassName()}[] \$data)");
        $baseClass->addComment("@method {$structure->getClassName()}|{$structure->getClassName()}[] fetchAll()");
        $baseClass->setExtends(Query::class);
        foreach ($structure->getProperties() as $prop) {
            self::addMethod($prop, $baseClass, $structure);
        }

        $constructor = $baseClass->addMethod('__construct')
            ->setBody("parent::__construct(\$repository, \$events);")
        ;
        $constructor->addParameter('repository')->setType($repositoryClass);
        $constructor->addParameter('events')->setType('bool')->setDefaultValue(true);
        $baseClass->addMethod('getRepository')
            ->setReturnType($repositoryClass)
            ->setBody(implode("\n", [
                'assert($this->repository instanceof ' . $structure->getClassName() . 'Repository);',
                'return $this->repository;',
            ]))
        ;

        $baseClass->addMethod('fetch')
            ->setReturnType($entityClass)
            ->setReturnNullable()
            ->setBody(implode("\n", [
                '$row = parent::fetch();',
                'if ($row instanceof ' . $structure->getClassName() . ') {',
                '    return $row;',
                '}',
                'return null;',
            ]))
        ;
        $baseClass->addMethod('createRow')
            ->setReturnType($entityClass)
            ->setBody(implode("\n", [
                'return $this->getRepository()->createRow();',
            ]))
        ;
        return $baseClass;
    }

    public static function writeQueryBase(EntityStructure $structure, string $appDir): void
    {
        $class = self::createBaseQuery($structure);
        $filename = $appDir . '/modules/Core/Repository/Generated/' . $class->getName() . '.php';
        if (!@mkdir(dirname($filename), 0777, true) && !is_dir(dirname($filename))) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', dirname($filename)));
        }
        $contents = "<?php\n\n" . $structure->getGeneratedNamespace()->__toString() . $class;
        $contents = str_replace("\t", '    ', $contents);
        file_put_contents($filename, $contents);
    }

    public static function addMethod(
        EntityProperty $prop,
        ClassType $baseClass,
        EntityStructure $structure
    ): void {
        $name = $prop->getName();
        $where = $baseClass->addMethod('where' . $structure->toPropertyName($name))
            ->setReturnType('self')
            ->setBody("return \$this->where(\"$name \$condition ?\", \$$name);")
        ;
        $where->addParameter($name)->setType($prop->getType());
        $where->addParameter('condition')->setType('string')->setDefaultValue('=');

        if (str_starts_with($prop->getType(), DateTimeInterface::class)) {
            $structure->getGeneratedNamespace()->addUse(DateTimeInterface::class);
            $before = $baseClass->addMethod('where' . $structure->toPropertyName($name) . 'Before')
                ->setReturnType('self')
                ->setBody("return \$this->where('$name <'.(\$orEquals ? '<=' : '<').' ?', \$before);")
                ->addComment('@param DateTimeInterface|string|int $before')
            ;
            $before->addParameter('before');
            $before->addParameter('orEquals')->setType('bool')->setDefaultValue(false);

            $after = $baseClass->addMethod('where' . $structure->toPropertyName($name) . 'After')
                ->setReturnType('self')
                ->setBody("return \$this->where('$name '.(\$orEquals ? '>=' : '>').' ?', \$after);")
                ->addComment('@param DateTimeInterface|string|int $after');
            $after->addParameter('after');
            $after->addParameter('orEquals')->setType('bool')->setDefaultValue(false);
        }

        if (str_starts_with($prop->getType(), 'string')) {
            $baseClass->addMethod('search' . $structure->toPropertyName($name))
                ->setReturnType('self')
                ->setBody("return \$this->where('$name LIKE ?', \"%\$$name%\");")
                ->addParameter($name)->setType($prop->getType())
            ;
        }

        if (!str_starts_with($prop->getType(), 'bool')) {
            $baseClass->addMethod('whereIn' . $structure->toPropertyName($name))
                ->setReturnType('self')
                ->setBody("return \$this->where('$name IN ?', \$$name);")
                ->addComment("@param {$prop->getType()} \$" . $name)
                ->addParameter($name)->setType('array')
            ;
        }
    }



    private static function createQuery(EntityStructure $structure): ClassType
    {
        $class = new ClassType($structure->getClassName() . 'Query', $structure->getQueryNamespace());
        $baseClassName = $structure->getGeneratedNamespace()->getName() . '\\' . $structure->getClassName() . 'QueryBase';
        $structure->getQueryNamespace()->addUse($baseClassName);
        $class->setExtends($baseClassName);
        return $class;
    }

    private static function modifyQuery(EntityStructure $structure): ClassType
    {
        $class = ClassType::from($structure->getQueryNamespace()->getName() . '\\' . $structure->getClassName() . 'Query', true);
        $baseClassName = $structure->getGeneratedNamespace()->getName() . '\\' . $structure->getClassName() . 'QueryBase';
        $class->getNamespace()->addUse($baseClassName);
        $class->setExtends($baseClassName);
        return $class;
    }

    public static function writeQuery(EntityStructure $structure, string $appDir): void
    {
        $repoClass = $structure->getQueryNamespace()->getName() . '\\' . $structure->getClassName() . 'Query';
        if (class_exists($repoClass)) {
            $class = self::modifyQuery($structure);
        } else {
            $class = self::createQuery($structure);
        }
        $namespace = $class->getNamespace();
        $structure->writeClass($class, $namespace, "$appDir/modules/Core/Repository/Query");
    }
}
