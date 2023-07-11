<?php

namespace Efabrica\NetteDatabaseRepository\CodeGen;

use Nette\Database\Structure;
use Nette\Neon\Entity;
use Nette\Neon\Neon;
use Nette\PhpGenerator\PhpNamespace;

class ModuleWriter
{

    public static function writeConfigNeon(EntityStructure $structure, string $dbDir): void
    {
        $config = [];
        if (file_exists($dbDir . '/config.neon')) {
            $config = file($dbDir . '/config.neon', FILE_IGNORE_NEW_LINES);
        }

        $repoClass = $structure->repositoryNamespace->getName() . '\\' . $structure->getClassName() . 'Repository';
        $serviceName = lcfirst($structure->getClassName()) . 'Repository';

        $defLine = null;
        $servicesLine = null;
        foreach ($config as $i => $line) {
            if (preg_match("/^\\s*$serviceName:/", $line) || str_contains($line, $repoClass)) {
                $defLine = $i;
                break;
            }
            if (preg_match("/^\\s*services:/", $line)) {
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
}
