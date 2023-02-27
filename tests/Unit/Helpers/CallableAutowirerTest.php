<?php

namespace Tests\Unit\Helpers;

use Efabrica\NetteDatabaseRepository\Helpers\CallableAutowirer;
use Examples\Repositories\UserRepository;
use Tests\TestCase;

class CallableAutowirerTest extends TestCase
{
    private CallableAutowirer $callableAutowirer;

    public function setUp(): void
    {
        parent::setUp();
        $this->callableAutowirer = new CallableAutowirer($this->container);
    }

    public function test_can_autowire_attributes(): void
    {
        $array = ['lorem' => 'ipsum'];
        $callable = function (UserRepository $userRepository, array $attributes) use ($array) {
            $this->assertEquals($array, $attributes);
        };

        $this->callableAutowirer->callMethod($callable, ['attributes' => $array]);
    }
}
