<?php

namespace Efabrica\NetteRepository\CodeGen;

use Doctrine\Inflector\Inflector;
use Efabrica\NetteRepository\CodeGen\EntityProperty;
use Nette\Database\Structure;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\Utils\Strings;
use RuntimeException;

/**
 * @internal
 */
class EntityStructure
{
    /** @var EntityProperty[] */
    private array $properties;

    private string $tableName;

    private string $className;

    public PhpNamespace $repositoryNamespace;

    public PhpNamespace $entityNamespace;

    public PhpNamespace $queryNamespace;

    public PhpNamespace $repositoryGenNamespace;

    public PhpNamespace $queryGenNamespace;

    public PhpNamespace $entityGenNamespace;

    public string $dbDir;

    public string $repositoryDir;

    public string $entityDir;

    public string $queryDir;

    public string $repositoryGenDir;

    public string $queryGenDir;

    public string $entityGenDir;

    private Inflector $inflector;

    private array $primaries;

    public array $toMany;

    public array $toOne;

    /** @var array [mnTable, selfColumn, otherColumn] */
    public array $manyToMany;

    /**
     * @param EntityProperty[] $properties
     */
    public function __construct(
        array $properties,
        string $table,
        string $namespace,
        string $dbDir,
        Inflector $inflector,
        array $primaries,
        Structure $structure
    ) {
        $this->tableName = $table;
        $this->inflector = $inflector;
        $this->className = $this->toClassName($table);
        $this->properties = $properties;

        $this->dbDir = $dbDir;
        $this->repositoryNamespace = new PhpNamespace($namespace . '\\Repository');
        $this->repositoryDir = $dbDir . '/Repository';
        $this->queryNamespace = new PhpNamespace($namespace . '\\Repository\\Query');
        $this->queryDir = $dbDir . '/Repository/Query';
        $this->repositoryGenNamespace = new PhpNamespace($namespace . '\\Repository\\Generated\\Repository');
        $this->repositoryGenDir = $dbDir . '/Repository/Generated/Repository';
        $this->queryGenNamespace = new PhpNamespace($namespace . '\\Repository\\Generated\\Query');
        $this->queryGenDir = $dbDir . '/Repository/Generated/Query';
        $this->entityNamespace = new PhpNamespace($namespace . '\\Repository\\Entity');
        $this->entityDir = $dbDir . '/Repository/Entity';
        $this->entityGenNamespace = new PhpNamespace($namespace . '\\Repository\\Generated\\Entity');
        $this->entityGenDir = $dbDir . '/Repository/Generated/Entity';

        $this->primaries = $primaries;

        $this->toMany = $structure->getHasManyReference($table) ?? [];
        $this->toOne = $structure->getBelongsToReference($table) ?? [];
        $this->manyToMany = $this->getManyToManyTables($structure);
    }

    private function getManyToManyTables(Structure $structure): array
    {
        $tables = [];
        foreach ($this->toMany as $mnTable => $mnColumns) {
            if ($mnTable === $this->tableName) {
                continue;
            }
            if (count($mnColumns) !== 1) {
                continue;
            }
            $toOne = $structure->getBelongsToReference($mnTable) ?? [];
            $refColumns = array_flip(array_diff($toOne, [$mnTable]));
            if (count($refColumns) !== 2) {
                continue;
            }
            $otherTable = array_diff($toOne, [$this->tableName]);
            $otherTable = reset($otherTable);
            $tables[$otherTable] = [$mnTable, $refColumns[$this->tableName], $refColumns[$otherTable]];
        }
        return $tables;
    }

    public function toClassName(string $string): string
    {
        return self::toClassCase($this->inflector, $string);
    }

    public static function toClassCase(Inflector $inflector, string $string): string
    {
        if (!Strings::endsWith($string, 'data')) {
            $string = $inflector->singularize($string);
        }
        return Strings::firstUpper($inflector->camelize($string));
    }

    public function toPluralName(string $column): string
    {
        $column = Strings::before($column, '_id') ?? $column;
        $column = $this->inflector->pluralize($column);
        return Strings::firstUpper($this->inflector->camelize($column));
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

    public function getPrimaries(): array
    {
        return $this->primaries;
    }

    public static function writeClass(ClassType $classType, string $dir): void
    {
        if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
        $contents = "<?php\n\n" . $classType->getNamespace() . $classType;
        $contents = str_replace("\t", '    ', $contents);
        file_put_contents($dir . '/' . $classType->getName() . '.php', $contents);
    }
}
