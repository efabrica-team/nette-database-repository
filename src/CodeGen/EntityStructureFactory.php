<?php

namespace Efabrica\NetteRepository\CodeGen;

use DateTimeInterface;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Efabrica\NetteRepository\Repository\Repository;
use Efabrica\NetteRepository\Traits\Cast\TypeOverrideBehavior;
use LogicException;
use Nette\Database\Structure;
use Nette\DI\Container;
use Nette\Utils\Strings;

class EntityStructureFactory
{
    private const TYPE_MAP = [
        'datetime' => DateTimeInterface::class,
        'date' => DateTimeInterface::class,
        'time' => DateTimeInterface::class,

        'tinyint' => 'int',
        'int' => 'int',
        'smallint' => 'int',
        'mediumint' => 'int',
        'bigint' => 'int',
        'tinyint unsigned' => 'int',
        'int unsigned' => 'int',
        'smallint unsigned' => 'int',
        'mediumint unsigned' => 'int',
        'bigint unsigned' => 'int',
        'timestamp' => 'int',
        'decimal' => 'string',

        'varchar' => 'string',
        'char' => 'string',
        'text' => 'string',
        'tinytext' => 'string',
        'mediumtext' => 'string',
        'longtext' => 'string',
        'json' => 'string',

        'blob' => 'string',
        'tinyblob' => 'string',
        'mediumblob' => 'string',
        'longblob' => 'string',

        'enum' => 'string',
    ];

    private Structure $structure;

    private Inflector $inflector;

    private Container $container;

    public function __construct(Structure $structure, Container $container)
    {
        $this->structure = $structure;
        $this->inflector = InflectorFactory::create()->build();
        $this->container = $container;
    }

    public function create(string $table, string $namespace, string $dbDir): EntityStructure
    {
        $tableAlias = $this->container->getParameter('netteRepository')['tableAlias'][$table] ?? null;
        $columns = $this->structure->getColumns($table);
        $primaries = [];
        $properties = [];
        foreach ($columns as $column) {
            $type = Strings::lower($column['nativetype']);

            $nativeType = $column['nativetype'];
            if (($column['size'] ?? null) !== null) {
                $nativeType .= '[' . $column['size'];
                if (($column['scale'] ?? null) !== null) {
                    $nativeType .= ',' . $column['scale'];
                }
                $nativeType .= ']';
            }
            $annotations = [];

            if ($column['default'] !== null) {
                $annotations[] = '@Default(' . var_export($column['default'], true) . ')';
            }
            if ($type === 'tinyint' && $column['size'] === 1) {
                $type = 'bool';
            } elseif (isset(self::TYPE_MAP[$type])) {
                $type = self::TYPE_MAP[$type];
            } else {
                throw new LogicException("Unknown type $type");
            }
            if ($column['nullable']) {
                $type .= '|null';
            }
            if ($column['primary']) {
                $annotations[] = '@Primary';
                $primaries[$column['name']] = $type;
            }
            if ($column['autoincrement']) {
                $annotations[] = '@AutoIncrement';
            }
            $properties[$column['name']] = new EntityProperty($type, $column['name'], implode(' ', $annotations), $nativeType);
        }

        $structure = new EntityStructure($properties, $table, $namespace, $dbDir, $this->inflector, $primaries, $this->structure, $tableAlias);

        $this->processTypeOverrides($structure);

        return $structure;
    }

    public function processTypeOverrides(EntityStructure $structure): void
    {
        if (!$this->container->hasService(ModuleWriter::getRepoServiceName($structure))) {
            return;
        }
        $repo = $this->container->getByName(ModuleWriter::getRepoServiceName($structure));
        if (!$repo instanceof Repository) {
            return;
        }

        $casts = [];
        foreach ($repo->getBehaviors()->all() as $behavior) {
            if ($behavior instanceof TypeOverrideBehavior) {
                foreach ($behavior->getFields() as $field) {
                    if ($behavior->getTypeOverride() !== null) {
                        $casts[$field] = $behavior->getTypeOverride();
                    }
                }
            }
        }

        foreach ($structure->getProperties() as $prop) {
            $propName = $casts[$prop->getName()] ?? null;
            if (isset($propName)) {
                if (str_contains($prop->getType(), '|null')) {
                    $propName .= '|null';
                }
                $prop->setType($propName);
            }
        }
    }
}
