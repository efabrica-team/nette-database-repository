<?php

namespace Tests\Feature\Repositories;

use Efabrica\NetteDatabaseRepository\Models\ActiveRow;
use Efabrica\NetteDatabaseRepository\Models\Managers\ManualModelFactoryManager;
use Efabrica\NetteDatabaseRepository\Repositores\Managers\ManualRepositoryManager;
use Efabrica\NetteDatabaseRepository\Selections\Factories\SelectionFactoryInterface;
use Efabrica\NetteDatabaseRepository\Selections\Selection;
use Examples\Models\Article;
use Examples\Models\User;
use Examples\Repositories\UserRepository;
use Examples\Selections\UserSelection;
use Tests\TestCase;

class UncompleteRepositoryTest extends TestCase
{
    private UserRepository $userRepository;

    private ManualRepositoryManager $repositoryManager;

    private ManualModelFactoryManager $modelFactoryManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->container->getByType(UserRepository::class);
        $this->repositoryManager = $this->container->getByType(ManualRepositoryManager::class);
        $this->modelFactoryManager = $this->container->getByType(ManualModelFactoryManager::class);
    }

    public function test_can_fetch_results_without_model(): void
    {
        $this->seedDatabase();

        $this->modelFactoryManager->unsetFactory('users');
        $user = $this->userRepository->query()->fetch();
        $this->assertInstanceOf(ActiveRow::class, $user);
        $this->assertNotInstanceOf(User::class, $user);
    }

    public function test_can_fetch_results_without_selection(): void
    {
        $this->seedDatabase();

        /** @var UserRepository $userRepository */
        $userRepository = $this->container->createInstance(UserRepository::class, [
            'selectionFactory' => $this->container->getByType(SelectionFactoryInterface::class)
        ]);

        $seleciton = $userRepository->query();
        $this->assertInstanceOf(Selection::class, $seleciton);
        $this->assertNotInstanceOf(UserSelection::class, $seleciton);
        $this->assertCount(9, $seleciton->fetchAll());
    }

    public function test_can_fetch_results_without_repository(): void
    {
        $this->seedDatabase();

        $this->repositoryManager->unsetRepository('articles');
        $user = $this->userRepository->query()->get(2);
        $articles = $user->related('articles')->fetchAll();
        $this->assertContainsOnlyInstancesOf(Article::class, $articles);
    }
}
