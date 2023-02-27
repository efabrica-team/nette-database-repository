<?php

namespace Tests\Unit\Repositories\Managers;

use Efabrica\NetteDatabaseRepository\Repositores\Managers\ManualRepositoryManager;
use Examples\Repositories\GroupRepository;
use Examples\Repositories\UserRepository;
use Tests\TestCase;

class ManualRepositoryManagerTest extends TestCase
{
    private ManualRepositoryManager $manualRepositoryManager;

    public function setUp(): void
    {
        parent::setUp();
        $this->manualRepositoryManager = new ManualRepositoryManager();
    }

    public function test_returns_null_on_unregistered_table(): void
    {
        $this->assertNull($this->manualRepositoryManager->createForTable('groups'));
    }

    public function test_can_return_correct_repository(): void
    {
        $this->manualRepositoryManager->addRepository('groups', $this->container->getByType(GroupRepository::class));
        $this->manualRepositoryManager->addRepository('users', $this->container->getByType(UserRepository::class));
        $this->assertInstanceOf(GroupRepository::class, $this->manualRepositoryManager->createForTable('groups'));
        $this->assertInstanceOf(UserRepository::class, $this->manualRepositoryManager->createForTable('users'));
    }
}
