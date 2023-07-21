<?php

namespace Efabrica\NetteDatabaseRepository\CodeGen;

class ModuleWriter
{
    public static function writeConfigNeon(EntityStructure $structure, string $dbDir): void
    {
        $config = [];
        if (file_exists($dbDir . '/config.neon')) {
            $config = file($dbDir . '/config.neon', FILE_IGNORE_NEW_LINES);
        }

        $repoClass = $structure->repositoryNamespace->getName() . '\\' . $structure->getClassName() . 'Repository';
        $serviceName = self::getRepoServiceName($structure);

        $defLine = null;
        $servicesLine = null;
        foreach ($config as $i => $line) {
            if (preg_match("/^\\s*$serviceName:/", $line) || str_contains($line, $repoClass)) {
                $defLine = $i;
                break;
            }
            if (preg_match('/^\\s*services:/', $line)) {
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

        file_put_contents($structure->dbDir . '/config.neon', implode("\n", $config));
    }

    public static function getRepoServiceName(EntityStructure $structure): string
    {
        return lcfirst($structure->getClassName()) . 'Repository';
    }
}
