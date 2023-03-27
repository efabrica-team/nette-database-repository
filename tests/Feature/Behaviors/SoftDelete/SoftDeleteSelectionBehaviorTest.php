<?php

namespace Tests\Feature\Behaviors\SoftDelete;

use Examples\Models\Article;
use Examples\Repositories\ArticleRepository;
use Nette\Database\Explorer;
use Tests\TestCase;

class SoftDeleteSelectionBehaviorTest extends TestCase
{
    private ArticleRepository $articleRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->articleRepository = $this->container->getByType(ArticleRepository::class);
    }

    public function test_can_apply_scopes(): void
    {
        $this->seedDatabase();

        $this->assertSame(6, $this->articleRepository->query()->count('*'));
        $this->articleRepository->delete(1);
        $this->assertSame(5, $this->articleRepository->query()->count('*'));
        $this->assertSame(6, $this->articleRepository->query()->withTrashed()->count('*'));
        $this->assertSame(1, $this->articleRepository->query()->onlyTrashed()->count('*'));
    }

    public function test_can_mass_delete(): void
    {
        $this->seedDatabase();

        $this->assertSame(6, $this->articleRepository->query()->count('*'));
        $this->articleRepository->query()->where('id <=', 3)->delete();
        $this->assertSame(3, $this->articleRepository->query()->count('*'));
        $this->assertSame(6, $this->articleRepository->query()->withTrashed()->count('*'));
    }

    public function test_can_mass_restore(): void
    {
        $this->seedDatabase();

        $this->assertSame(6, $this->articleRepository->query()->count('*'));
        $this->articleRepository->query()->where('id <=', 3)->delete();
        $this->assertSame(3, $this->articleRepository->query()->count('*'));
        $this->articleRepository->query()->onlyTrashed()->restore();
        $this->assertSame(6, $this->articleRepository->query()->count('*'));
    }

    public function test_can_mass_force_delete(): void
    {
        $this->seedDatabase();

        $this->assertSame(6, $this->articleRepository->query()->count('*'));
        $this->articleRepository->query()->where('id <=', 3)->forceDelete();
        $this->assertSame(3, $this->articleRepository->query()->count('*'));
        $this->assertSame(3, $this->articleRepository->query()->withTrashed()->count('*'));
    }
}
