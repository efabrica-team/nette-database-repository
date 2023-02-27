<?php

namespace Tests\Unit;

use Examples\Repositories\UserRepository;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDatabase();
        $this->userRepository = $this->container->getByType(UserRepository::class);
    }

    public function testExample(): void
    {
        $this->assertSame(9, $this->userRepository->query()->count('*'));
    }
}
