<?php

namespace Tests\Unit\Models\Managers;

use Efabrica\NetteDatabaseRepository\Models\Factories\ModelFactoryInterface;
use Efabrica\NetteDatabaseRepository\Models\Managers\ManualModelFactoryManager;
use Examples\Models\Factories\GroupModelFactory;
use Tests\TestCase;

class ManualModelFactoryManagerTest extends TestCase
{
    private ModelFactoryInterface $defaultModelFactory;

    private ManualModelFactoryManager $manualModelFactoryManager;

    public function setUp(): void
    {
        parent::setUp();
        /** @var ModelFactoryInterface $defaultModelFactory */
        $defaultModelFactory = $this->container->getByType(ModelFactoryInterface::class);
        $this->defaultModelFactory = $defaultModelFactory;
        $this->manualModelFactoryManager = new ManualModelFactoryManager($this->defaultModelFactory);
    }

    public function test_returns_default_on_unregistered_table(): void
    {
        $this->assertSame($this->defaultModelFactory, $this->manualModelFactoryManager->createForTable('unregistered'));
    }

    public function test_can_return_correct_repository(): void
    {
        /** @var GroupModelFactory $groupModelFactory */
        $groupModelFactory = $this->container->createService('groupModelFactory');
        $this->manualModelFactoryManager->addFactory('groups', $groupModelFactory);
        $this->assertSame($groupModelFactory, $this->manualModelFactoryManager->createForTable('groups'));
    }
}
