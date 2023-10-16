<?php

namespace Efabrica\NetteRepository\CodeGen;

use DateTimeInterface;
use Efabrica\NetteRepository\Model\Entity;
use Nette\Database\Table\GroupedSelection;
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

        foreach ($structure->toOne as $relatedColumn => $relatedTable) {
            /** @var EntityStructure $relatedStructure */
            $relatedStructure = $structures[$relatedTable];
            $className = $relatedStructure->getClassName();
            $columnName = Strings::before($relatedColumn, '_id') ?? $relatedColumn;
            // if $relatedColumn does not end with _id, add getter instead of property
            if ($columnName === $relatedColumn) {
                $relatedEntity = $relatedStructure->entityGenNamespace->getName() . '\\' . $className;
                $relatedRepository = $relatedStructure->repositoryNamespace->getName() . '\\' . $className . 'Repository';
                $structure->entityGenNamespace->addUse($relatedEntity);
                $structure->entityGenNamespace->addUse($relatedRepository);
                $RELATED_COLUMN = mb_strtoupper($relatedColumn);
                $class->addMethod('get' . $structure->toClassName($columnName))
                    ->setBody("\$row = \$this->ref({$className}Repository::TABLE_NAME, self::$RELATED_COLUMN);\n" .
                        "assert(\$row === null || \$row instanceof {$className});\n" .
                        'return $row;')
                    ->setReturnType($relatedEntity)
                    ->setReturnNullable()
                ;
            } else {
                $class->addComment("@property {$className}|null \${$columnName} @ForeignKey('$relatedTable')");
            }
        }
        foreach ($structure->toMany as $relatedTable => $relatedColumns) {
            if (count($relatedColumns) > 1) {
                continue;
            }
            [$relatedColumn] = $relatedColumns;
            /** @var EntityStructure $relatedStructure */
            $relatedStructure = $structures[$relatedTable];
            $className = $relatedStructure->getClassName();
            $relatedEntity = $relatedStructure->entityGenNamespace->getName() . '\\' . $className;
            $relatedQuery = $relatedStructure->queryNamespace->getName() . '\\' . $className . 'Query';
            $relatedRepository = $relatedStructure->repositoryNamespace->getName() . '\\' . $className . 'Repository';
            $structure->entityGenNamespace->addUse($relatedEntity);
            $structure->entityGenNamespace->addUse(GroupedSelection::class);
            $structure->entityGenNamespace->addUse($relatedRepository);
            $RELATED_COLUMN = mb_strtoupper($relatedColumn);
            $body = "/** @var iterable<{$className}>&GroupedSelection \$query */\n" .
                "\$query = \$this->related({$className}Repository::TABLE_NAME, $className::$RELATED_COLUMN);\n" .
                'return $query;';

            $class->addMethod('get' . $structure->toPluralName($className))
                ->setBody($body)
                ->setReturnType(GroupedSelection::class)
                ->addComment("@return iterable<{$className}>&GroupedSelection")
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
