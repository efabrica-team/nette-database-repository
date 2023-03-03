<?php

namespace Tests\Feature\Repositories;

use Efabrica\NetteDatabaseRepository\Exceptions\RepositoryException;
use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Repositores\Managers\ManualRepositoryManager;
use Examples\Repositories\UserRepository as BaseUserRepository;
use Tests\TestCase;

class CustomRepositoryFunctionalityTest extends TestCase
{
    private CustomUserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container->getByType(ManualRepositoryManager::class)->setRepositories([
            'users' => $this->userRepository = $this->getUserRepository(),
        ]);
    }

    public function test_can_rollback_insert_on_repository_exception(): void
    {
        $this->assertCount(0, $this->userRepository->query()->fetchAll());
        $this->expectException(RepositoryException::class);
        $this->userRepository->insert([
            'name' => 'John Doe',
            'email' => 'john@doe.com'
        ]);
        $this->assertCount(0, $this->userRepository->query()->fetchAll());
    }

    public function test_can_rollback_update_on_repository_exception(): void
    {
        $this->seedDatabase();

        $user = $this->userRepository->query()->get(1);
        $this->assertSame('Admin', $user->name);
        $this->expectException(RepositoryException::class);
        $this->userRepository->update($user, ['name' => 'Jane Dane']);
        $this->assertSame('Admin', $user->name);
        $user = $this->userRepository->query()->get(1);
        $this->assertSame('Admin', $user->name);
    }

    public function test_can_rollback_delete_on_repository_exception(): void
    {
        $this->seedDatabase();

        $this->assertCount(9, $this->userRepository->query()->fetchAll());
        $user = $this->userRepository->query()->get(1);
        $this->expectException(RepositoryException::class);
        $this->userRepository->delete($user);
        $this->assertCount(9, $this->userRepository->query()->fetchAll());
    }

    private function getUserRepository(): CustomUserRepository
    {
        /** @var CustomUserRepository $userRepository */
        $userRepository = $this->container->createInstance(CustomUserRepository::class);
        return $userRepository;
    }
}

class CustomUserRepository extends BaseUserRepository
{
    final public function afterInsertThrowException(ActiveRow $record, array $data): void
    {
        throw new RepositoryException();
    }

    final public function afterUpdateThrowException(ActiveRow $oldRecord, ActiveRow $newRecord, array $data): void
    {
        throw new RepositoryException();
    }

    final public function afterDeleteThrowException(ActiveRow $record): void
    {
        throw new RepositoryException();
    }
}
