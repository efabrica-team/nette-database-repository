<?php

namespace Efabrica\NetteRepository\CodeGen;

use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use ReflectionClass;
use RuntimeException;

class FileWriter
{
    private array $writtenFiles = [];

    private array $freshFiles = [];

    private array $inheritance;

    public function __construct(array $inheritance)
    {
        $this->inheritance = $inheritance;
    }

    /**
     * @param class-string|string $class
     */
    public static function isGenericClass(string $class): bool
    {
        // a missing base class is written by the generator itself, with @template
        if (!class_exists($class)) {
            return true;
        }
        $docComment = (new ReflectionClass($class))->getDocComment();
        return $docComment !== false && str_contains($docComment, '@template');
    }

    public function writeClass(ClassLike $classType, string $dir): void
    {
        $inheritance = $this->inheritance[$classType->getName()] ?? [];
        if (isset($inheritance['extends']) && $classType instanceof ClassType) {
            $classType->setExtends($inheritance['extends']);
        }
        $namespace = $classType->getNamespace();
        assert($namespace !== null);
        if (isset($inheritance['implements'])) {
            foreach ((array)$inheritance['implements'] as $interface) {
                $namespace->addUse($interface);
                if ($classType instanceof ClassType) {
                    $classType->addImplement($interface);
                }
            }
        }
        $contents = "<?php\n\n" . $namespace . $classType;
        $contents = str_replace("\t", '    ', $contents);
        $contents = preg_replace('/\n{3,}/', "\n\n", $contents) ?? $contents;
        $this->writeFile($dir . '/' . $classType->getName() . '.php', $contents);
    }

    public function writeFile(string $path, string $contents): void
    {
        if (file_exists($path) && file_get_contents($path) === $contents) {
            $this->freshFiles[$path] = true;
            return;
        }
        $dir = dirname($path);
        if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
        file_put_contents($path, $contents);
        $this->writtenFiles[$path] = true;
    }

    public function getWrittenFiles(): array
    {
        return $this->writtenFiles;
    }

    public function getFreshFiles(): array
    {
        return $this->freshFiles;
    }
}
