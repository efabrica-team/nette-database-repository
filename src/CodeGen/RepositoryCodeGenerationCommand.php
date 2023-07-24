<?php

namespace Efabrica\NetteDatabaseRepository\CodeGen;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Nette\Database\Structure;
use Nette\DI\Container;
use Nette\PhpGenerator\ClassType;
use Nette\Utils\Strings;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RepositoryCodeGenerationCommand extends Command
{
    private string $appDir;

    private Inflector $inflector;

    private string $repoDir;

    private string $namespace;

    private EntityStructureFactory $structureFactory;

    private Structure $structure;

    private Container $container;

    public function __construct(string $appDir, EntityStructureFactory $structureFactory, Structure $structure, Container $container)
    {
        parent::__construct('repository:code-gen');
        $this->inflector = InflectorFactory::create()->build();
        $this->appDir = $appDir;
        $this->repoDir = 'modules/Core';
        $this->namespace = 'App\\Core';
        $this->structureFactory = $structureFactory;
        $this->structure = $structure;
        $this->container = $container;
    }

    public function findRepoDirs(array $tables, array &$repoDirs, array &$repoNamespaces): void
    {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->appDir));
        /** @var SplFileInfo $file */
        foreach ($rii as $file) {
            if (!str_ends_with($file->getPathname(), '.php')) {
                continue;
            }
            foreach ($tables as $table) {
                $className = EntityStructure::toClassCase($this->inflector, $table['name']);
                if ($file->getBaseName('.php') === $className . 'Repository') {
                    $c = ClassType::fromCode(file_get_contents($file->getPathname()));
                    $repoDirs[$table['name']] = Strings::before($file->getPathname(), '/Repositor') ?? dirname($file->getPathname(), 2);
                    $repoNamespaces[$table['name']] = Strings::before(
                        $c->getNamespace()->getName(),
                        '\\Repositor'
                    ) ?? $c->getNamespace()->getName();
                }
            }
        }
    }

    protected function configure(): void
    {
        $this->addOption('migrate', null, null, 'Migrate existing repositories to new structure');
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
        $repoDirs = [];
        $repoNamespaces = [];
        foreach ($tables as $table) {
            $classNames[$table['name']] = EntityStructure::toClassCase($this->inflector, $table['name']);
        }

        $this->findRepoDirs($tables, $classNames, $repoDirs, $repoNamespaces);

        foreach ($tables as $table) {
            $namespace = $repoNamespaces[$table['name']] ?? $this->namespace;
            $dbDir = $repoDirs[$table['name']] ?? ($this->appDir . '/' . $this->repoDir);

            $structure = $this->structureFactory->create($table['name'], $namespace, $dbDir);
            $output->writeln("Generating {$structure->getClassName()} Entity Structure");
            EntityWriter::writeBody($structure);
            $output->writeln("Generating {$structure->getClassName()} Entity");
            EntityWriter::writeEntity($structure, $this->container);
            $output->writeln("Generating {$structure->getClassName()} Query Base");
            QueryWriter::writeQueryBase($structure);
            $output->writeln("Generating {$structure->getClassName()} Query");
            QueryWriter::writeQuery($structure);
            $output->writeln("Generating {$structure->getClassName()} Repository Base");
            RepositoryWriter::writeRepositoryBase($structure);
            $output->writeln("Generating {$structure->getClassName()} Repository");
            RepositoryWriter::writeRepository($structure, $input->getOption('migrate'));
            ModuleWriter::writeConfigNeon($structure, $dbDir);
        }
        return 0;
    }
}
