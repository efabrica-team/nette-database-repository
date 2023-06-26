<?php

namespace Efabrica\NetteDatabaseRepository\CodeGen;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Nette\Database\Structure;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class DatabaseCodeGenerationCommand extends Command
{
    private Structure $structure;
    private string $appDir;
    private EntityWriter $entityWriter;
    private Inflector $inflector;

    public function __construct(string $appDir, Structure $structure)
    {
        parent::__construct('database:code-generation');
        $this->structure = $structure;
        $this->inflector = InflectorFactory::create()->build();
        $this->appDir = $appDir;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tables = $this->structure->getTables();
        foreach ($tables as $table) {
            $structure = new EntityStructure($this->structure, $this->inflector, $table['name']);
            $output->writeln("Generating {$structure->getClassName()} Entity");
            EntityWriter::writeEntity($structure, $this->appDir);
            $output->writeln("Generating {$structure->getClassName()} Query Base");
            QueryWriter::writeQueryBase($structure, $this->appDir);
            $output->writeln("Generating {$structure->getClassName()} Query");
            QueryWriter::writeQuery($structure, $this->appDir);
            $output->writeln("Generating {$structure->getClassName()} Repository Base");
            RepositoryWriter::writeRepositoryBase($structure, $this->appDir);
            $output->writeln("Generating {$structure->getClassName()} Repository");
            RepositoryWriter::writeRepository($structure, $this->appDir);
        }
        return 0;
    }
}
