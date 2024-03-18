<?php

namespace Efabrica\NetteRepository\CodeGen;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Nette\Database\Structure;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\Utils\Strings;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
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

    /**
     * @var object{ignoreTables: array, configNeonPath: string, inheritance: array{extends: string, implements: array<string>}[]}
     */
    private object $config;

    /**
     * @param object{ignoreTables: array, configNeonPath: string, inheritance: array{extends: string, implements: array<string>}[]} $config
     */
    public function __construct(string $appDir, object $config, EntityStructureFactory $structureFactory, Structure $structure)
    {
        parent::__construct('efabrica:nette-repo:code-gen');
        $this->inflector = InflectorFactory::create()->build();
        $this->appDir = $appDir;
        $this->repoDir = 'modules/Core';
        $this->namespace = 'App\\Core';
        $this->structureFactory = $structureFactory;
        $this->structure = $structure;
        $this->config = $config;
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
                if ($file->getBasename('.php') === $className . 'Repository') {
                    $code = file_get_contents($file->getPathname());
                    if ($code === false) {
                        throw new RuntimeException("Cannot read file {$file->getPathname()}");
                    }
                    $c = ClassType::fromCode($code);
                    $repoDirs[$table['name']] = Strings::before($file->getPathname(), '/Repositor') ?? dirname($file->getPathname(), 2);
                    /** @var PhpNamespace $phpNamespace */
                    $phpNamespace = $c->getNamespace();
                    $repoNamespaces[$table['name']] = Strings::before($phpNamespace->getName(), '\\Repositor') ?? $phpNamespace->getName();
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

        $this->findRepoDirs($tables, $repoDirs, $repoNamespaces);

        $structures = [];
        foreach ($tables as $table) {
            if ($this->config->ignoreTables[$table['name']] ?? false) {
                continue;
            }
            $namespace = $repoNamespaces[$table['name']] ?? $this->namespace;
            $dbDir = $repoDirs[$table['name']] ?? ($this->appDir . '/' . $this->repoDir);
            $structures[$table['name']] = $this->structureFactory->create($table['name'], $namespace, $dbDir);
        }
        if (!isset($dbDir)) {
            if ($tables === []) {
                throw new RuntimeException('No database table found');
            }
            throw new RuntimeException('No non-ignored database table found');
        }

        $writer = new FileWriter($this->config->inheritance);
        /** @var bool $migrate */
        $migrate = $input->getOption('migrate');

        foreach ($structures as $structure) {
            $output->writeln("Generating {$structure->getClassName()} Repository App Base");
            RepositoryWriter::writeAppRepositoryBase($structure, $writer);
            $output->writeln("Generating {$structure->getClassName()} Query App Base");
            QueryWriter::writeAppQueryBase($structure, $writer);

            $output->writeln("Generating {$structure->getClassName()} Entity Structure");
            EntityWriter::writeBody($structure, $writer);
            $output->writeln("Generating {$structure->getClassName()} Entity");
            EntityWriter::writeEntity($structure, $structures, $writer);
            $output->writeln("Generating {$structure->getClassName()} Query Base");
            QueryWriter::writeQueryBase($structure, $writer);
            $output->writeln("Generating {$structure->getClassName()} Query");
            QueryWriter::writeQuery($structure, $writer);
            $output->writeln("Generating {$structure->getClassName()} Repository Base");
            RepositoryWriter::writeRepositoryBase($structure, $writer);
            $output->writeln("Generating {$structure->getClassName()} Repository");
            RepositoryWriter::writeRepository($structure, $migrate, $writer);

            ModuleWriter::writeConfigNeon($structure, $writer, $this->config->configNeonPath);
        }
        return 0;
    }
}
