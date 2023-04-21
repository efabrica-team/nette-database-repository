<?php

namespace Tests\Feature\Models;

use Examples\Models\Group;
use Examples\Repositories\UserRepository;
use Nette\Database\Table\ActiveRow;
use Tests\TestCase;

class BaseModelFunctionalityTest extends TestCase
{
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->container->getByType(UserRepository::class);
    }

    public function test_can_update_record(): void
    {
        $user = $this->userRepository->insert([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
        ]);
        $this->assertSame('John Doe', $user->name);
        $this->assertSame('john@doe.com', $user->email);

        $user->update([
            'name' => 'Jane Dane',
            'email' => 'jane@dane.com',
        ]);
        $this->assertSame('Jane Dane', $user->name);
        $this->assertSame('jane@dane.com', $user->email);
    }

    public function test_can_delete_record(): void
    {
        $this->seedDatabase();

        $this->assertCount(9, $this->userRepository->query()->fetchAll());
        $user = $this->userRepository->query()->get(2);
        $user->delete();
        $this->assertCount(8, $this->userRepository->query()->fetchAll());
        $this->assertNull($this->userRepository->query()->get(2));
    }

    public function test_can_fetch_ref_record(): void
    {
        $this->seedDatabase();

        $user = $this->userRepository->query()->get(2);
        $userGroup = $user->group;
        $this->assertSame(2, $userGroup->id);

        /** @var ?Group $userGroup */
        $userGroup = $user->ref('groups', 'group_id');
        $this->assertSame(2, $userGroup->id);
    }

    public function test_can_fetch_related_records(): void
    {
        $this->seedDatabase();

        $user = $this->userRepository->query()->get(3);
        $articles = $user->related('articles')->fetchAll();
        $this->assertCount(2, $articles);
        $this->assertContainsOnlyInstancesOf(ActiveRow::class, $articles);
        $this->assertArrayHasKey(3, $articles);
        $this->assertArrayHasKey(4, $articles);
    }
}
