<?php

namespace Tests\Feature\Behaviors\SoftDelete;

use Examples\Repositories\ArticleRepository;
use Tests\TestCase;

class SoftDeleteModelBehaviorTest extends TestCase
{
    private ArticleRepository $articleRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->articleRepository = $this->container->getByType(ArticleRepository::class);
    }

    public function test_can_delete(): void
    {
        $this->seedDatabase();

        $this->assertSame(6, $this->articleRepository->query()->count('*'));
        $this->articleRepository->query()->get(1)->delete();
        $this->assertSame(5, $this->articleRepository->query()->count('*'));
    }

    public function test_can_restore(): void
    {
        $this->seedDatabase();

        $this->assertSame(6, $this->articleRepository->query()->count('*'));
        $this->articleRepository->delete(1);
        $this->assertSame(5, $this->articleRepository->query()->count('*'));
        $this->articleRepository->query()->withTrashed()->get(1)->restore();
        $this->assertSame(6, $this->articleRepository->query()->count('*'));
    }

    public function test_can_force_delete(): void
    {
        $this->seedDatabase();

        $this->assertSame(6, $this->articleRepository->query()->count('*'));
        $this->articleRepository->query()->withTrashed()->get(1)->forceDelete();
        $this->assertSame(5, $this->articleRepository->query()->count('*'));
        $this->assertSame(5, $this->articleRepository->query()->withTrashed()->count('*'));
    }

    public function test_can_determine_if_deleted(): void
    {
        $this->seedDatabase();

        $this->assertSame(6, $this->articleRepository->query()->count('*'));
        $this->assertFalse($this->articleRepository->query()->get(1)->isDeleted());
        $this->articleRepository->query()->get(1)->delete();
        $this->assertSame(5, $this->articleRepository->query()->count('*'));
        $this->assertTrue($this->articleRepository->query()->withTrashed()->get(1)->isDeleted());
    }
}
