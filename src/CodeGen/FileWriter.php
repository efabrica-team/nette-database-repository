<?php

namespace Efabrica\NetteRepository\CodeGen;

use Nette\PhpGenerator\ClassType;
use RuntimeException;

class FileWriter
{
    private array $writtenFiles = [];

    private array $freshFiles = [];

    public function writeClass(ClassType $classType, string $dir): void
    {
        $contents = "<?php\n\n" . $classType->getNamespace() . $classType;
        $contents = str_replace("\t", '    ', $contents);
        $this->writeFile($dir . '/' . $classType->getName() . '.php', $contents);
    }

    /**
     * @param string    $dir
     * @param ClassType $classType
     * @param           $contents
     * @return void
     */
    public function writeFile(string $path, $contents): void
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
