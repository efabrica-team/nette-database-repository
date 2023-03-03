<?php

namespace Tests\Feature\Repositories;

use Examples\Repositories\UserRepository;
use Nette\Database\Table\ActiveRow;
use Tests\TestCase;

class BaseRepositoryFunctionalityTest extends TestCase
{
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->container->getByType(UserRepository::class);
    }

    public function test_can_fetch(): void
    {
        $this->seedDatabase();

        $this->assertCount(9, $this->userRepository->query()->fetchAll());

        $users = $this->userRepository->query()->fetchPairs('id', 'name');
        $this->assertSame(1, array_keys($users)[0]);
        $this->assertSame('Admin', $users[1]);

        $user = $this->userRepository->query()->fetch();
        $this->assertInstanceOf(ActiveRow::class, $user);
        $this->assertSame(1, $user->id);

        $user = $this->userRepository->query()->get(2);
        $this->assertInstanceOf(ActiveRow::class, $user);
        $this->assertSame(2, $user->id);

        foreach ($this->userRepository->query() as $user) {
            $this->assertInstanceOf(ActiveRow::class, $user);
        }
    }

    public function test_can_apply_conditions(): void
    {
        $this->seedDatabase();

        $this->assertCount(4, $this->userRepository->query()->where('group_id IS NOT NULL')->fetchAll());
    }

    public function test_can_insert_record(): void
    {
        $this->assertSame(0, $this->userRepository->query()->count());
        $user = $this->userRepository->insert([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
        ]);
        $this->assertSame(1, $this->userRepository->query()->count());
        $this->assertSame(1, $user->id);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@doe.com', $user->email);
    }

    public function test_can_insert_many_records(): void
    {
        $this->assertSame(0, $this->userRepository->query()->count());
        $this->userRepository->insertMany([
            [
                'name' => 'John Doe',
                'email' => 'john@doe.com',
            ],
            [
                'name' => 'Jane Dane',
                'email' => 'jane@dane.com',
            ]
        ]);
        $this->assertSame(2, $this->userRepository->query()->count());
    }

    public function test_can_update_record(): void
    {
        $user = $this->userRepository->insert([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
        ]);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@doe.com', $user->email);

        $this->userRepository->update($user, [
            'name' => 'Jane Dane',
            'email' => 'jane@dane.com',
        ]);
        $this->assertSame('Jane Dane', $user->name);
        $this->assertSame('jane@dane.com', $user->email);
    }

    public function test_can_update_record_by_primary(): void
    {
        $user = $this->userRepository->insert([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
        ]);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@doe.com', $user->email);

        $user = $this->userRepository->update($user->id, [
            'name' => 'Jane Dane',
            'email' => 'jane@dane.com',
        ]);
        $this->assertSame('Jane Dane', $user->name);
        $this->assertSame('jane@dane.com', $user->email);
    }

    public function test_update_returns_null_if_record_does_not_exist(): void
    {
        $user = $this->userRepository->update(1, [
            'name' => 'Jane Dane',
            'email' => 'jane@dane.com',
        ]);
        $this->assertNull($user);
    }

    public function test_can_delete_record(): void
    {
        $this->seedDatabase();

        $this->assertCount(9, $this->userRepository->query()->fetchAll());
        $user = $this->userRepository->query()->get(2);
        $this->userRepository->delete($user);
        $this->assertCount(8, $this->userRepository->query()->fetchAll());
        $this->assertNull($this->userRepository->query()->get(2));
    }

    public function test_can_delete_record_by_primary(): void
    {
        $user = $this->userRepository->insert([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
        ]);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@doe.com', $user->email);

        $this->userRepository->delete($user->id);
        $this->assertNull($this->userRepository->query()->get($user->id));
    }

    public function test_delete_returns_false_if_record_does_not_exist(): void
    {
        $this->assertFalse($this->userRepository->delete(1));
    }
}
