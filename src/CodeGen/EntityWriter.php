<?php

namespace Efabrica\NetteRepository\CodeGen;

use DateTimeInterface;
use Efabrica\NetteRepository\Model\Entity;
use Efabrica\NetteRepository\Repository\GroupedQuery;
use Efabrica\NetteRepository\Repository\Query;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Strings;
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
            $class->addComment($prop->toString());
            if (str_contains($prop->getType(), DateTimeInterface::class)) {
                $structure->entityGenNamespace->addUse(DateTimeInterface::class);
            }
            $class->addConstant(mb_strtoupper($prop->getName()), $prop->getName())->setPublic();
        }

        $ignoreTables = [];
        foreach ($structure->manyToMany as $relatedTable => [$mnTable, $selfColumn, $otherColumn]) {
            /** @var EntityStructure $relatedStructure */
            $relatedStructure = $structures[$relatedTable];
            $relatedClassName = $relatedStructure->getClassName();
            $relatedEntity = $relatedStructure->entityGenNamespace->getName() . '\\' . $relatedClassName;
            $mnStructure = $structures[$mnTable];
            $mnClassName = $mnStructure->getClassName();
            $structure->entityGenNamespace->addUse($relatedEntity);
            $structure->entityGenNamespace->addUse(Query::class);
            $structure->entityGenNamespace->addUse($relatedStructure->repositoryNamespace->getName() . '\\' . $relatedClassName . 'Repository');
            $structure->entityGenNamespace->addUse($mnStructure->entityGenNamespace->getName() . '\\' . $mnClassName);
            $structure->entityGenNamespace->addUse($mnStructure->repositoryNamespace->getName() . '\\' . $mnClassName . 'Repository');
            $SELF_COLUMN = mb_strtoupper($selfColumn);
            $OTHER_COLUMN = mb_strtoupper($otherColumn);

            $class->addMethod('get' . $structure->toPluralName($relatedClassName))
                ->setBody(
                    "/** @var iterable<{$relatedClassName}>&Query<{$relatedClassName}> \$query */" .
                    "\$query = \$this->relatedManyToMany({$mnClassName}Repository::class, {$relatedClassName}Repository::class, $mnClassName::$SELF_COLUMN, $mnClassName::$OTHER_COLUMN);\n" .
                    'return $query;'
                )
                ->setReturnType(Query::class)
                ->addComment("@return iterable<{$relatedClassName}>")
                ->addComment("@phpstan-return Query<{$relatedClassName}>")
            ;

            $relatedPrimaryType = implode('|', $relatedStructure->getPrimaries());
            $body = "\$this->setRelatedManyToMany({$mnClassName}Repository::class, $mnClassName::$SELF_COLUMN, $mnClassName::$OTHER_COLUMN, \$owned);\nreturn \$this;";
            $class->addMethod('set' . $structure->toPluralName($relatedClassName))
                ->setBody($body)
                ->setReturnType('self')
                ->addComment("@param iterable<{$relatedClassName}|$relatedPrimaryType> \$owned")
                ->addComment('@return $this')
                ->addParameter('owned')->setType('iterable')
            ;
            $ignoreTables[] = $mnTable;
        }

        foreach ($structure->toOne as $relatedColumn => $relatedTable) {
            if (in_array($relatedTable, $ignoreTables, true)) {
                continue;
            }
            /** @var EntityStructure $relatedStructure */
            $relatedStructure = $structures[$relatedTable];
            $relatedClassName = $relatedStructure->getClassName();
            $columnName = Strings::before($relatedColumn, '_id') ?? $relatedColumn;
            // if $relatedColumn does not end with _id, add getter instead of property
            if ($columnName === $relatedColumn) {
                $relatedEntity = $relatedStructure->entityGenNamespace->getName() . '\\' . $relatedClassName;
                $relatedRepository = $relatedStructure->repositoryNamespace->getName() . '\\' . $relatedClassName . 'Repository';
                $structure->entityGenNamespace->addUse($relatedEntity);
                $structure->entityGenNamespace->addUse($relatedRepository);
                $RELATED_COLUMN = mb_strtoupper($relatedColumn);
                $class->addMethod('get' . $structure->toClassName($columnName))
                    ->setBody("\$row = \$this->ref({$relatedClassName}Repository::TABLE_NAME, self::$RELATED_COLUMN);\n" .
                        "assert(\$row === null || \$row instanceof {$relatedClassName});\n" .
                        'return $row;')
                    ->setReturnType($relatedEntity)
                    ->setReturnNullable()
                ;
            } else {
                $class->addComment("@property {$relatedClassName}|null \${$columnName} @ForeignKey('$relatedTable')");
            }
        }
        foreach ($structure->toMany as $relatedTable => $relatedColumns) {
            if (in_array($relatedTable, $ignoreTables, true)) {
                continue;
            }
            if (count($relatedColumns) > 1) {
                continue;
            }
            [$relatedColumn] = $relatedColumns;
            /** @var EntityStructure $relatedStructure */
            $relatedStructure = $structures[$relatedTable];
            $relatedClassName = $relatedStructure->getClassName();
            $relatedEntity = $relatedStructure->entityGenNamespace->getName() . '\\' . $relatedClassName;
            $relatedRepository = $relatedStructure->repositoryNamespace->getName() . '\\' . $relatedClassName . 'Repository';
            $structure->entityGenNamespace->addUse($relatedEntity);
            $structure->entityGenNamespace->addUse(GroupedQuery::class);
            $structure->entityGenNamespace->addUse($relatedRepository);
            $RELATED_COLUMN = mb_strtoupper($relatedColumn);
            $body = "/** @var iterable<{$relatedClassName}>&GroupedQuery \$query */\n" .
                "\$query = \$this->related({$relatedClassName}Repository::TABLE_NAME, $relatedClassName::$RELATED_COLUMN);\n" .
                'return $query;';

            $class->addMethod('get' . $structure->toPluralName($relatedClassName))
                ->setBody($body)
                ->setReturnType(GroupedQuery::class)
                ->addComment("@return iterable<{$relatedClassName}>")
                ->addComment('@phpstan-return GroupedQuery')
            ;
        }
        return $class;
    }

    public static function writeBody(EntityStructure $structure, FileWriter $writer): void
    {
        if (!class_exists($structure->entityNamespace->getName() . '\\' . $structure->getClassName() . 'Body')) {
            $writer->writeClass(self::createBody($structure), $structure->entityDir);
        }
    }

    public static function writeEntity(EntityStructure $structure, array $structures, FileWriter $writer): void
    {
        $entityClassName = "{$structure->entityNamespace->getName()}\\{$structure->getClassName()}";
        if (!class_exists($entityClassName)) {
            $entity = self::createClass($structure, $structures);
            $writer->writeClass($entity, $structure->entityGenDir);
        }
    }
}
