<?php

namespace Tests\Unit;

use Efabrica\NetteRepository\Repository\Entity;
use PHPUnit\Framework\TestCase;

class EntitySameValueTest extends TestCase
{
    public function testIsSameValue(): void
    {
        $dt = new \DateTimeImmutable('2021-01-01 00:00:00', new \DateTimeZone('UTC'));

        $this->assertTrue(Entity::isSameValue(true, 1));
        $this->assertTrue(Entity::isSameValue(false, 0));
        $this->assertTrue(Entity::isSameValue(5, '5'));
        $this->assertTrue(Entity::isSameValue(5.0, '5'));
        $this->assertTrue(Entity::isSameValue($dt, '2021-01-01T00:00:00+00:00'));
        $this->assertFalse(Entity::isSameValue(1.1, 1.2));
    }
}
