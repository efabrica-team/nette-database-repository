<?php

namespace Tests\Unit\Models;

use Efabrica\NetteDatabaseRepository\Models\Factories\ModelFactoryInterface;
use Efabrica\NetteDatabaseRepository\Selections\Factories\SelectionFactoryInterface;
use Tests\TestCase;

class ActiveRowTest extends TestCase
{
    private SelectionFactoryInterface $selectionFactory;

    private ModelFactoryInterface $modelFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->selectionFactory = $this->container->getByType(SelectionFactoryInterface::class);
        $this->modelFactory = $this->container->getByType(ModelFactoryInterface::class);
    }

    public function test_can_use_dynamic_properties(): void
    {
        $activeRow = $this->modelFactory->create([], $this->selectionFactory->create('users'));

        $this->assertFalse(isset($activeRow->param));
        $activeRow->param = 'ipsum';
        $this->assertEquals('ipsum', $activeRow->param);
        unset($activeRow->param);
        $this->assertFalse(isset($activeRow->param));
    }

    public function test_can_get_data_as_array(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John',
        ];
        $activeRow = $this->modelFactory->create($data, $this->selectionFactory->create('users'));

        $this->assertEquals($data, $activeRow->toArray());
        $this->assertEquals($data, $activeRow->getData());
    }
}
