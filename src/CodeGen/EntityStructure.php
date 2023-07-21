<?php

namespace Efabrica\NetteDatabaseRepository\CodeGen;

use DateTimeInterface;
use Doctrine\Inflector\Inflector;
use Efabrica\NetteDatabaseRepository\CodeGen\EntityProperty;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\Utils\Strings;
use RuntimeException;

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

    /**
     * @param EntityProperty[] $properties
     */
    public function __construct(array $properties, string $table, string $namespace, string $dbDir, Inflector $inflector, array $primaries)
    {
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

        foreach ($properties as $property) {
            if ($property->getType() === DateTimeInterface::class) {
                $this->entityNamespace->addUse(DateTimeInterface::class);
                break;
            }
        }
        $this->primaries = $primaries;
    }

    public function toClassName(string $string): string
    {
        return self::toClassCase($this->inflector, $string);
    }

    public static function toClassCase(Inflector $inflector, string $string)
    {
        if (!Strings::endsWith($string, 'data')) {
            $string = $inflector->singularize($string);
        }
        return Strings::firstUpper($inflector->camelize($string));
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

    public function getPrimaries(): array
    {
        return $this->primaries;
    }

    public function writeClass(ClassType $classType, string $dir): void
    {
        if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
        $contents = "<?php\n\n" . $classType->getNamespace() . $classType;
        $contents = str_replace("\t", '    ', $contents);
        file_put_contents($dir . '/' . $classType->getName() . '.php', $contents);
    }
}
