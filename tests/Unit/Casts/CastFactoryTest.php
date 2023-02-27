<?php

namespace Tests\Unit\Casts;

use Efabrica\NetteDatabaseRepository\Casts\Factories\CastFactory;
use Efabrica\NetteDatabaseRepository\Casts\JsonArrayCast;
use Examples\Repositories\UserRepository;
use RuntimeException;
use Tests\TestCase;

class CastFactoryTest extends TestCase
{
    private CastFactory $castFactory;

    public function setUp(): void
    {
        parent::setUp();
        $this->castFactory = new CastFactory($this->container);
    }

    public function test_throws_exception_on_invalid_type(): void
    {
        $this->expectException(RuntimeException::class);
        $this->castFactory->createFromType(UserRepository::class);
    }

    public function test_returns_cast(): void
    {
        $this->assertInstanceOf(JsonArrayCast::class, $this->castFactory->createFromType(JsonArrayCast::class));
    }
}
