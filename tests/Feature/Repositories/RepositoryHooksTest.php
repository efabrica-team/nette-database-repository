<?php

namespace Tests\Feature\Repositories;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Repositores\Managers\ManualRepositoryManager;
use Examples\Repositories\UserRepository as BaseUserRepository;
use Nette\Database\Table\Selection;
use Tests\HasEvents;
use Tests\TestCase;

class RepositoryHooksTest extends TestCase
{
    private HookUserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container->getByType(ManualRepositoryManager::class)->setRepositories([
            'users' => $this->userRepository = $this->getUserRepository(),
        ]);
    }

    public function test_default_conditions_hook(): void
    {
        $this->seedDatabase();
        $this->assertSame(9, $this->database->table('users')->count());
        $this->assertSame(4, $this->userRepository->query()->count());
    }

    public function test_select_hook(): void
    {
        $this->seedDatabase();
        $this->assertFalse($this->userRepository->wasEventFired('beforeSelect'));
        $this->assertFalse($this->userRepository->wasEventFired('afterSelect'));
        $this->userRepository->query()->fetchAll();
        $this->assertTrue($this->userRepository->wasEventFired('beforeSelect'));
        $this->assertTrue($this->userRepository->wasEventFired('afterSelect'));
    }

    public function test_insert_hook(): void
    {
        $this->assertFalse($this->userRepository->wasEventFired('beforeInsert'));
        $this->assertFalse($this->userRepository->wasEventFired('afterInsert'));
        $this->userRepository->insert([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
        ]);
        $this->assertTrue($this->userRepository->wasEventFired('beforeInsert'));
        $this->assertTrue($this->userRepository->wasEventFired('afterInsert'));
    }

    public function test_update_hook(): void
    {
        $this->seedDatabase();

        $this->assertFalse($this->userRepository->wasEventFired('beforeUpdate'));
        $this->assertFalse($this->userRepository->wasEventFired('afterUpdate'));
        $this->userRepository->update(1, [
            'name' => 'John Doe',
            'email' => 'john@doe.com',
        ]);
        $this->assertTrue($this->userRepository->wasEventFired('beforeUpdate'));
        $this->assertTrue($this->userRepository->wasEventFired('afterUpdate'));
    }

    public function test_delete_hook(): void
    {
        $this->seedDatabase();

        $this->assertFalse($this->userRepository->wasEventFired('beforeDelete'));
        $this->assertFalse($this->userRepository->wasEventFired('afterDelete'));
        $this->userRepository->delete(1);
        $this->assertTrue($this->userRepository->wasEventFired('beforeDelete'));
        $this->assertTrue($this->userRepository->wasEventFired('afterDelete'));
    }

    private function getUserRepository(): HookUserRepository
    {
        /** @var HookUserRepository $userRepository */
        $userRepository = $this->container->createInstance(HookUserRepository::class);
        return $userRepository;
    }
}

class HookUserRepository extends BaseUserRepository
{
    use HasEvents;

    final public function defaultConditionsActionName(Selection $selection): void
    {
        $selection->where('group_id IS NOT NULL');
    }

    final public function beforeSelectActionName(Selection $selection): void
    {
        $this->fireEvent('beforeSelect');
    }

    final public function afterSelectActionName(Selection $selection): void
    {
        $this->fireEvent('afterSelect');
    }

    final public function beforeInsertActionName(array $data): array
    {
        $this->fireEvent('beforeInsert');
        return $data;
    }

    final public function afterInsertActionName(ActiveRow $record, array $data): void
    {
        $this->fireEvent('afterInsert');
    }

    final public function beforeUpdateActionName(ActiveRow $record, array $data): array
    {
        $this->fireEvent('beforeUpdate');
        return $data;
    }

    final public function afterUpdateActionName(ActiveRow $oldRecord, ActiveRow $newRecord, array $data): void
    {
        $this->fireEvent('afterUpdate');
    }

    final public function beforeDeleteActionName(ActiveRow $record): void
    {
        $this->fireEvent('beforeDelete');
    }

    final public function afterDeleteActionName(ActiveRow $record): void
    {
        $this->fireEvent('afterDelete');
    }
}
