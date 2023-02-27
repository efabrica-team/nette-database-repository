<?php

namespace Tests;

use Efabrica\NetteDatabaseRepository\Repositores\Repository;
use Nette\Bootstrap\Configurator;
use Nette\Database\Explorer;
use Nette\DI\Container;
use Nette\Utils\FileSystem;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Container $container;

    protected Explorer $database;

    protected function setUp(): void
    {
        $configurator = new Configurator();
        $configurator->addStaticParameters(['appDir' => __DIR__ . '/..']);
        $configurator->setTempDirectory(__DIR__ . '/../temp');
        $configurator->addConfig(__DIR__ . '/../examples/config.neon');

        $this->container = $configurator->createContainer();
        $this->database = $this->container->getByType(Explorer::class);

        foreach (explode(';', file_get_contents(__DIR__ . '/../examples/Database/structure.sql')) as $query) {
            $this->database->query($query);
        }
    }

    protected function tearDown(): void
    {
        $this->database->getConnection()->disconnect();
        FileSystem::delete(__DIR__ . '/../temp');
    }

    protected function seedDatabase(): void
    {
        foreach (explode(';', file_get_contents(__DIR__ . '/../examples/Database/data.sql')) as $query) {
            $this->database->query($query);
        }
    }
}
