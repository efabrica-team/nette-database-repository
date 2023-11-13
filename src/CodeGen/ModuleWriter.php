<?php

namespace Efabrica\NetteRepository\CodeGen;

use Doctrine\Inflector\Inflector;
use RuntimeException;

class ModuleWriter
{
    public static function writeConfigNeon(EntityStructure $structure, string $dbDir, FileWriter $writer): void
    {
        $configPath = $dbDir . '/config.neon';
        if (is_file($configPath) === false) {
            $writer->writeFile($configPath, "services:\n");
        }
        $config = file($configPath, FILE_IGNORE_NEW_LINES);
        if ($config === false) {
            throw new RuntimeException("Cannot read file $configPath");
        }

        $repoClass = $structure->repositoryNamespace->getName() . '\\' . $structure->getClassName() . 'Repository';
        $serviceName = self::getRepoServiceName($structure);

        $defLine = null;
        $servicesLine = null;
        foreach ($config as $i => $line) {
            if (preg_match("/^\\s*$serviceName:/", $line) === 1) {
                $defLine = $i;
                break;
            }
            if (preg_match('/^\\s*services:/', $line) === 1) {
                $servicesLine = $i;
            }
        }

        if ($defLine === null) {
            if ($servicesLine === null) {
                $config[] = 'services:';
                $servicesLine = count($config) - 1;
            }
            array_splice($config, $servicesLine + 1, 0, ['    ' . $serviceName . ': ' . $repoClass]);
        }

        $writer->writeFile($structure->dbDir . '/config.neon', implode("\n", $config));
    }

    public static function getRepoServiceName(EntityStructure $structure): string
    {
        return lcfirst($structure->getClassName()) . 'Repository';
    }

    public static function toRepoServiceName(string $table, Inflector $inflector): string
    {
        return lcfirst(EntityStructure::toClassCase($inflector, $table)) . 'Repository';
    }
}
