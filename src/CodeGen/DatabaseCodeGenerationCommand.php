<?php

namespace Efabrica\NetteDatabaseRepository\CodeGen;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Nette\Database\Structure;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Strings;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class DatabaseCodeGenerationCommand extends Command
{
    private string $appDir;
    private Inflector $inflector;
    private string $repoDir;
    private string $namespace;
    private EntityStructureFactory $structureFactory;
    private Structure $structure;

    public function __construct(string $appDir, EntityStructureFactory $structureFactory, Structure $structure)
    {
        parent::__construct('database:generate-code');
        $this->inflector = InflectorFactory::create()->build();
        $this->appDir = $appDir;
        $this->repoDir = 'modules/Core';
        $this->namespace = 'App\\Core';
        $this->structureFactory = $structureFactory;
        $this->structure = $structure;
    }

    public function setRepoDir(string $repoDir): self
    {
        $this->repoDir = $repoDir;
        return $this;
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tables = $this->structure->getTables();
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->appDir));
        $files = [];
        $classNames = [];
        $repoDirs = [];
        $repoNamespaces = [];
        foreach ($tables as $table) {
            $classNames[$table['name']] = EntityStructure::toClassCase($this->inflector, $table['name']);
        }
        /** @var \SplFileInfo $file */
        foreach ($rii as $file) {
            if (!str_ends_with($file->getPathname(), '.php')) {
                continue;
            }
            foreach ($tables as $table) {
                if ($file->getBaseName('.php') === $classNames[$table['name']] . 'Repository') {
                    $repoDirs[$table['name']] = Strings::before($file->getPathname(), '/Repositor') ?? dirname($file->getPathname(), 2);
                    $c = ClassType::fromCode(file_get_contents($file->getPathname()));
                    $repoNamespaces[$table['name']] = Strings::before($c->getNamespace()->getName(), '\\Repositor') ?? $c->getNamespace()->getName();
                }
            }
        }

        foreach ($tables as $table) {
            $namespace = $repoNamespaces[$table['name']] ?? $this->namespace;
            $dbDir = $repoDirs[$table['name']] ?? ($this->appDir . '/' . $this->repoDir);

            $structure = $this->structureFactory->create($table['name'], $namespace, $dbDir);
            $output->writeln("Generating {$structure->getClassName()} Entity");
            EntityWriter::writeEntity($structure);
            $output->writeln("Generating {$structure->getClassName()} Query Base");
            QueryWriter::writeQueryBase($structure);
            $output->writeln("Generating {$structure->getClassName()} Query");
            QueryWriter::writeQuery($structure);
            $output->writeln("Generating {$structure->getClassName()} Repository Base");
            RepositoryWriter::writeRepositoryBase($structure);
            $output->writeln("Generating {$structure->getClassName()} Repository");
            RepositoryWriter::writeRepository($structure);
            ModuleWriter::writeConfigNeon($structure, $dbDir);
        }
        return 0;
    }
}
