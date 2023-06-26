<?php

namespace Efabrica\NetteDatabaseRepository\CodeGen;

use DateTimeInterface;
use Doctrine\Inflector\Inflector;
use Efabrica\NetteDatabaseRepository\Model\EntityProperty;
use LogicException;
use Nette\Database\Structure;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\Utils\Strings;
use RuntimeException;

class EntityStructure
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

    /** @var EntityProperty[] */
    private array $properties = [];
    private string $tableName;
    private string $className;
    private PhpNamespace $repositoryNamespace;
    private PhpNamespace $entityNamespace;
    private PhpNamespace $queryNamespace;
    private PhpNamespace $generatedNamespace;

    public function __construct(Structure $structure, Inflector $inflector, string $table)
    {
        $this->tableName = $table;
        $this->inflector = $inflector;
        $this->className = $this->toClassName($table);
        $this->repositoryNamespace = new PhpNamespace('App\\Core\\Repository');
        $this->entityNamespace = new PhpNamespace('App\\Core\\Repository\\Entity');
        $this->queryNamespace = new PhpNamespace('App\\Core\\Repository\\Query');
        $this->generatedNamespace = new PhpNamespace('App\\Core\\Repository\\Generated');
        $columns = $structure->getColumns($table);
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
            if ($type === DateTimeInterface::class) {
                $this->entityNamespace->addUse(DateTimeInterface::class);
            }
            if ($column['nullable']) {
                $type .= '|null';
            }
            $this->properties[$column['name']] = new EntityProperty('', $type, $column['name'], $nativeType, $annotation);
        }
    }

    public function toClassName(string $string): string
    {
        if (!Strings::endsWith($string, 'data')) {
            $string = $this->inflector->singularize($string);
        }
        return $this->toPropertyName($string);
    }

    public function toPropertyName(string $string): string
    {
        return Strings::firstUpper($this->inflector->camelize($string));
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return EntityProperty[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getRepositoryNamespace(): PhpNamespace
    {
        return $this->repositoryNamespace;
    }

    public function getEntityNamespace(): PhpNamespace
    {
        return $this->entityNamespace;
    }

    public function getQueryNamespace(): PhpNamespace
    {
        return $this->queryNamespace;
    }

    public function getGeneratedNamespace(): PhpNamespace
    {
        return $this->generatedNamespace;
    }

    public function writeClass(ClassType $classType, PhpNamespace $namespace, string $dir): void
    {
        if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
        $contents = "<?php\n\n" . $namespace . $classType;
        $contents = str_replace("\t", '    ', $contents);
        file_put_contents($dir . '/' . $classType->getName() . '.php', $contents);
    }
}
