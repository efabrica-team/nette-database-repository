<?php

namespace Efabrica\NetteRepository\CodeGen;

use DateTimeInterface;
use Efabrica\NetteRepository\Repository\Entity;
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
        foreach ($structure->manyToMany as $relTable => [$mnTable, $selfColumn, $otherColumn]) {
            /** @var EntityStructure $relStructure */
            $relStructure = $structures[$relTable];
            $relClassName = $relStructure->getClassName();
            $relEntity = $relStructure->entityGenNamespace->getName() . '\\' . $relClassName;
            $mnStructure = $structures[$mnTable];
            $mnClassName = $mnStructure->getClassName();
            $structure->entityGenNamespace->addUse($relEntity);
            $structure->entityGenNamespace->addUse(Query::class);
            $structure->entityGenNamespace->addUse($relStructure->repositoryNamespace->getName() . '\\' . $relClassName . 'Repository');
            $structure->entityGenNamespace->addUse($mnStructure->entityGenNamespace->getName() . '\\' . $mnClassName);
            $structure->entityGenNamespace->addUse($mnStructure->repositoryNamespace->getName() . '\\' . $mnClassName . 'Repository');
            $SELF_COLUMN = mb_strtoupper($selfColumn);
            $OTHER_COLUMN = mb_strtoupper($otherColumn);

            $class->addMethod('get' . $structure->toPluralName($relClassName))
                ->setBody(
                    "/** @var iterable<{$relClassName}>&Query<{$relClassName}> \$query */\n" .
                    "\$query = \$this->relatedThrough({$mnClassName}Repository::class, {$relClassName}Repository::class, $mnClassName::$SELF_COLUMN, $mnClassName::$OTHER_COLUMN);\n" .
                    'return $query;'
                )
                ->setReturnType(Query::class)
                ->addComment("@return iterable<{$relClassName}>")
                ->addComment("@phpstan-return Query<{$relClassName}>")
            ;

            $relatedPrimaryType = implode('|', $relStructure->getPrimaries());
            $body = "\$this->setRelatedThrough({$mnClassName}Repository::class, $mnClassName::$SELF_COLUMN, $mnClassName::$OTHER_COLUMN, \$owned);\nreturn \$this;";
            $class->addMethod('set' . $structure->toPluralName($relClassName))
                ->setBody($body)
                ->setReturnType('self')
                ->addComment("@param iterable<{$relClassName}|$relatedPrimaryType> \$owned")
                ->addComment('@return $this')
                ->addParameter('owned')->setType('iterable')
            ;
            $ignoreTables[] = $mnTable;
        }

        foreach ($structure->toOne as $relColumn => $relTable) {
            /** @var EntityStructure $relStructure */
            $relStructure = $structures[$relTable];
            $relClassName = $relStructure->getClassName();
            $columnName = Strings::before($relColumn, '_id') ?? $relColumn;
            // if $relColumn does not end with _id, add getter instead of property
            if ($columnName === $relColumn) {
                $relEntity = $relStructure->entityGenNamespace->getName() . '\\' . $relClassName;
                $relRepo = $relStructure->repositoryNamespace->getName() . '\\' . $relClassName . 'Repository';
                $structure->entityGenNamespace->addUse($relEntity);
                $structure->entityGenNamespace->addUse($relRepo);
                $RELATED_COLUMN = mb_strtoupper($relColumn);
                $class->addMethod('get' . $structure->toClassName($columnName))
                    ->setBody("\$row = \$this->ref({$relClassName}Repository::TABLE_NAME, self::$RELATED_COLUMN);\n" .
                        "assert(\$row === null || \$row instanceof {$relClassName});\n" .
                        'return $row;')
                    ->setReturnType($relEntity)
                    ->setReturnNullable()
                ;
            } else {
                $relNullable = str_contains($structure->getProperties()[$relColumn]->getType(), '|null') ? '|null' : '';
                $class->addComment("@property {$relClassName}{$relNullable} \${$columnName} @ForeignKey('$relTable')");
            }
        }
        foreach ($structure->toMany as $relTable => $relColumns) {
            if (count($relColumns) > 1) {
                continue;
            }
            [$relatedColumn] = $relColumns;
            /** @var EntityStructure $relStructure */
            $relStructure = $structures[$relTable];
            $relClassName = $relStructure->getClassName();
            $relEntity = $relStructure->entityGenNamespace->getName() . '\\' . $relClassName;
            $relRepo = $relStructure->repositoryNamespace->getName() . '\\' . $relClassName . 'Repository';
            $structure->entityGenNamespace->addUse($relEntity);
            $structure->entityGenNamespace->addUse(GroupedQuery::class);
            $structure->entityGenNamespace->addUse($relRepo);
            $RELATED_COLUMN = mb_strtoupper($relatedColumn);
            $body = "/** @var iterable<{$relClassName}>&GroupedQuery \$query */\n" .
                "\$query = \$this->related({$relClassName}Repository::TABLE_NAME, $relClassName::$RELATED_COLUMN);\n" .
                'return $query;';

            $class->addMethod('get' . $structure->toPluralName($relClassName))
                ->setBody($body)
                ->setReturnType(GroupedQuery::class)
                ->addComment("@return iterable<{$relClassName}>")
                ->addComment('@phpstan-return GroupedQuery')
            ;
        }
        return $class;
    }

    public static function writeBody(EntityStructure $structure, FileWriter $writer): void
    {
        if (!trait_exists($structure->entityNamespace->getName() . '\\' . $structure->getClassName() . 'Body')) {
            $writer->writeClass(self::createBody($structure), $structure->entityDir);
        }
    }

    public static function writeEntity(EntityStructure $structure, array $structures, FileWriter $writer): void
    {
        $entity = self::createClass($structure, $structures);
        $writer->writeClass($entity, $structure->entityGenDir);
    }
}
