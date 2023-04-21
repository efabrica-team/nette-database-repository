<?php

namespace Tests\Unit\Models\Managers;

use Efabrica\NetteDatabaseRepository\Models\Factories\ClassModelFactory;
use Efabrica\NetteDatabaseRepository\Models\Factories\ModelFactoryInterface;
use Efabrica\NetteDatabaseRepository\Models\Managers\ModelFactoryManager;
use Examples\Models\Group;
use Tests\TestCase;

class ManualModelFactoryManagerTest extends TestCase
{
    private ModelFactoryInterface $defaultModelFactory;

    private ModelFactoryManager $manualModelFactoryManager;

    public function setUp(): void
    {
        parent::setUp();
        /** @var ModelFactoryInterface $defaultModelFactory */
        $defaultModelFactory = $this->container->getByType(ModelFactoryInterface::class);
        $this->defaultModelFactory = $defaultModelFactory;
        $this->manualModelFactoryManager = new ModelFactoryManager($this->container, $this->defaultModelFactory);
    }

    public function test_returns_default_on_unregistered_table(): void
    {
        $this->assertSame($this->defaultModelFactory, $this->manualModelFactoryManager->createForTable('unregistered'));
    }

    public function test_can_return_correct_repository(): void
    {
        /** @var ClassModelFactory $groupModelFactory */
        $groupModelFactory = $this->container->createInstance(ClassModelFactory::class, ['modelClass' => Group::class]);
        $this->manualModelFactoryManager->addFactory('groups', $groupModelFactory);
        $this->assertSame($groupModelFactory, $this->manualModelFactoryManager->createForTable('groups'));
    }
}
