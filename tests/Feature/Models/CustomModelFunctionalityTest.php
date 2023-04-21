<?php

namespace Tests\Feature\Models;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Repositores\Managers\ManualRepositoryManager;
use Examples\Repositories\ArticleRepository as BaseArticleRepository;
use Examples\Repositories\GroupRepository as BaseGroupRepository;
use Examples\Repositories\UserRepository as BaseUserRepository;
use Nette\Database\Table\Selection;
use RuntimeException;
use Tests\TestCase;

class CustomModelFunctionalityTest extends TestCase
{
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container->getByType(ManualRepositoryManager::class)->setRepositories([
            'users' => $this->userRepository = $this->getUserRepository(),
            'groups' => $this->getGroupRepository(),
            'articles' => $this->getArticleRepository(),
        ]);
    }

    public function test_model_updates_using_repository(): void
    {
        $this->seedDatabase();

        $user = $this->userRepository->query()->get(1);
        $this->expectExceptionMessage(UserRepository::BEFORE_UPDATE);
        $user->update([
            'name' => 'Jane Dane',
        ]);
    }

    public function test_model_deletes_using_repository(): void
    {
        $this->seedDatabase();

        $user = $this->userRepository->query()->get(1);
        $this->expectExceptionMessage(UserRepository::BEFORE_DELETE);
        $user->delete();
    }

    public function test_model_ref_using_repository(): void
    {
        $this->seedDatabase();

        $user = $this->userRepository->query()->get(1);
        $this->expectExceptionMessage(GroupRepository::BEFORE_SELECT);
        $this->assertNotNull($user->group);
    }

    public function test_model_related_using_repository(): void
    {
        $this->seedDatabase();

        $user = $this->userRepository->query()->get(1);
        $this->expectExceptionMessage(ArticleRepository::BEFORE_SELECT);
        $user->related('articles')->fetchAll();
    }

    private function getUserRepository(): UserRepository
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->container->createInstance(UserRepository::class);
        return $userRepository;
    }

    private function getGroupRepository(): GroupRepository
    {
        /** @var GroupRepository $groupRepository */
        $groupRepository = $this->container->createInstance(GroupRepository::class);
        return $groupRepository;
    }

    private function getArticleRepository(): ArticleRepository
    {
        /** @var ArticleRepository $articleRepository */
        $articleRepository = $this->container->createInstance(ArticleRepository::class);
        return $articleRepository;
    }
}

class UserRepository extends BaseUserRepository
{
    public const BEFORE_UPDATE = 'Before update hook executed';

    public const BEFORE_DELETE = 'Before delete hook executed';

    final public function beforeUpdateThrowException(ActiveRow $record, array $data): array
    {
        throw new RuntimeException(self::BEFORE_UPDATE);
    }

    final public function beforeDeleteThrowException(ActiveRow $record): void
    {
        throw new RuntimeException(self::BEFORE_DELETE);
    }
}

class ArticleRepository extends BaseArticleRepository
{
    public const BEFORE_SELECT = 'Before select hook executed';

    final public function beforeSelectActionName(Selection $selection): void
    {
        throw new RuntimeException(self::BEFORE_SELECT);
    }
}

class GroupRepository extends BaseGroupRepository
{
    public const BEFORE_SELECT = 'Before select hook executed';

    final public function beforeSelectActionName(Selection $selection): void
    {
        throw new RuntimeException(self::BEFORE_SELECT);
    }
}
