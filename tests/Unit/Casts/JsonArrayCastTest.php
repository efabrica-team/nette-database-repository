<?php

namespace Tests\Unit\Casts;

use Efabrica\NetteDatabaseRepository\Casts\Factories\CastFactory;
use Efabrica\NetteDatabaseRepository\Casts\JsonArrayCast;
use Efabrica\NetteDatabaseRepository\Models\Factories\ModelFactoryInterface;
use Efabrica\NetteDatabaseRepository\Selections\Factories\SelectionFactoryInterface;
use Examples\Models\Factories\GroupModelFactory;
use Examples\Selections\Factories\GroupSelectionFactory;
use Tests\TestCase;

class JsonArrayCastTest extends TestCase
{
    private JsonArrayCast $arrayCast;

    private SelectionFactoryInterface $selectionFactory;

    private ModelFactoryInterface $modelFactory;

    public function setUp(): void
    {
        parent::setUp();

        $selectionFactory = $this->container->getByType(GroupSelectionFactory::class);
        $modelFactory = $this->container->getByType(GroupModelFactory::class);
        $castFactory = new CastFactory($this->container);

        $this->arrayCast = $castFactory->createFromType(JsonArrayCast::class);
        $this->selectionFactory = $selectionFactory;
        $this->modelFactory = $modelFactory;
    }

    public function test_returns_null_on_null_value(): void
    {
        $this->assertNull($this->arrayCast->get($this->modelFactory->create([], $this->selectionFactory->create('groups')), 'array', null, []));
    }

    public function test_returns_array_on_encoded_string_value(): void
    {
        $array = ['lorem' => 'ipsum', 'dolor' => 'sit'];
        $this->assertEquals($array, $this->arrayCast->get($this->modelFactory->create([], $this->selectionFactory->create('groups')), 'array', json_encode($array), []));
    }

    public function test_returns_array_on_object_value(): void
    {
        $array = ['lorem' => 'ipsum', 'dolor' => 'sit'];
        $this->assertEquals($array, $this->arrayCast->get($this->modelFactory->create([], $this->selectionFactory->create('groups')), 'array', (object)$array, []));
    }

    public function test_returns_array_on_array_value(): void
    {
        $array = ['lorem' => 'ipsum', 'dolor' => 'sit'];
        $this->assertEquals($array, $this->arrayCast->get($this->modelFactory->create([], $this->selectionFactory->create('groups')), 'array', $array, []));
    }

    public function test_returns_string_on_array_or_object_value(): void
    {
        $array = ['lorem' => 'ipsum', 'dolor' => 'sit'];
        $this->assertEquals(json_encode($array), $this->arrayCast->set($this->modelFactory->create([], $this->selectionFactory->create('groups')), 'array', $array, []));
        $this->assertEquals(json_encode($array), $this->arrayCast->set($this->modelFactory->create([], $this->selectionFactory->create('groups')), 'array', (object)$array, []));
    }

    public function test_returns_string_on_string_value(): void
    {
        $array = json_encode(['lorem' => 'ipsum', 'dolor' => 'sit']);
        $this->assertEquals($array, $this->arrayCast->set($this->modelFactory->create([], $this->selectionFactory->create('groups')), 'array', $array, []));
    }
}
