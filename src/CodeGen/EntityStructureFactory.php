<?php

namespace Efabrica\NetteDatabaseRepository\CodeGen;

use DateTimeInterface;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Efabrica\NetteDatabaseRepository\Model\EntityProperty;
use LogicException;
use Nette\Database\Structure;
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
        'timestamp' => 'int',
        'decimal' => 'string',

        'varchar' => 'string',
        'char' => 'string',
        'text' => 'string',
        'tinytext' => 'string',
        'mediumtext' => 'string',
        'longtext' => 'string',

        'blob' => 'string',
        'tinyblob' => 'string',
        'mediumblob' => 'string',
        'longblob' => 'string',

        'enum' => 'string',
    ];

    private Structure $structure;
    private Inflector $inflector;

    public function __construct(Structure $structure)
    {
        $this->structure = $structure;
        $this->inflector = InflectorFactory::create()->build();
    }

    public function create(string $table, string $namespace, string $dbDir): EntityStructure
    {
        $columns = $this->structure->getColumns($table);
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
            $annotation = '';

            if ($type === 'json') {
                $type = 'array';
                $annotation .= ' @JSON';
            } elseif ($type === 'tinyint' && $column['size'] === 1) {
                $type = 'bool';
            } elseif (isset(self::TYPE_MAP[$type])) {
                $type = self::TYPE_MAP[$type];
            } else {
                throw new LogicException("Unknown type $type");
            }
            if ($column['nullable']) {
                $type .= '|null';
            }
            $properties[$column['name']] = new EntityProperty('', $type, $column['name'], $nativeType, $annotation);
        }
        return new EntityStructure($properties, $table, $namespace, $dbDir, $this->inflector);
    }
}
