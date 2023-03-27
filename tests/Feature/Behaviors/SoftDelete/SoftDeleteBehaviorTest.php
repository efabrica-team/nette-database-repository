<?php

namespace Tests\Feature\Behaviors\SoftDelete;

use Examples\Models\Article;
use Examples\Repositories\ArticleRepository;
use Nette\Database\Explorer;
use Tests\TestCase;

class SoftDeleteBehaviorTest extends TestCase
{
    private Explorer $explorer;

    private ArticleRepository $articleRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->explorer = $this->container->getByType(Explorer::class);
        $this->articleRepository = $this->container->getByType(ArticleRepository::class);
    }

    public function test_delete_will_update_deleted_at_column(): void
    {
        $this->seedDatabase();

        $this->assertSame(6, $this->articleRepository->query()->count('*'));
        $this->articleRepository->delete(1);
        $this->assertSame(6, $this->explorer->table('articles')->count('*'));
        /** @var Article $record */
        $record = $this->explorer->table('articles')->get(1);
        $this->assertNotNull($record);
        $this->assertNotNull($record->deleted_at);
    }

    public function test_deleted_items_will_not_be_fetched_by_default(): void
    {
        $this->seedDatabase();
        $this->assertSame(6, $this->articleRepository->query()->count('*'));
        $this->articleRepository->delete(1);
        $this->assertSame(5, $this->articleRepository->query()->count('*'));
        $this->assertNull($this->articleRepository->query()->get(1));
    }

    public function test_can_restore_items(): void
    {
        $this->seedDatabase();
        $this->assertSame(6, $this->articleRepository->query()->count('*'));
        $this->articleRepository->delete(1);
        $this->assertSame(5, $this->articleRepository->query()->count('*'));
        $this->articleRepository->restore(1);
        $this->assertSame(6, $this->articleRepository->query()->count('*'));
    }

    public function test_can_force_delete(): void
    {
        $this->seedDatabase();
        $this->assertSame(6, $this->articleRepository->query()->count('*'));
        $this->articleRepository->forceDelete(1);
        $this->assertSame(5, $this->articleRepository->query()->count('*'));
        $this->assertSame(5, $this->explorer->table('articles')->count('*'));
    }
}
