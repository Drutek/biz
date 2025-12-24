<?php

use App\Livewire\News\Index;
use App\Models\NewsItem;
use App\Models\TrackedEntity;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('News Index', function () {
    it('renders the index page', function () {
        $this->get(route('news.index'))
            ->assertSuccessful()
            ->assertSeeLivewire(Index::class);
    });

    it('displays news items', function () {
        $entity = TrackedEntity::factory()->create();
        NewsItem::factory()->for($entity, 'trackedEntity')->create([
            'title' => 'Breaking Tech News',
        ]);

        Livewire::test(Index::class)
            ->assertSee('Breaking Tech News');
    });

    it('filters news by entity', function () {
        $entity1 = TrackedEntity::factory()->create(['name' => 'Company A']);
        $entity2 = TrackedEntity::factory()->create(['name' => 'Company B']);

        NewsItem::factory()->for($entity1, 'trackedEntity')->create(['title' => 'News about A']);
        NewsItem::factory()->for($entity2, 'trackedEntity')->create(['title' => 'News about B']);

        Livewire::test(Index::class)
            ->set('entityFilter', $entity1->id)
            ->assertSee('News about A')
            ->assertDontSee('News about B');
    });

    it('filters news by read status', function () {
        $entity = TrackedEntity::factory()->create();
        NewsItem::factory()->for($entity, 'trackedEntity')->create([
            'title' => 'Unread News',
            'is_read' => false,
        ]);
        NewsItem::factory()->for($entity, 'trackedEntity')->create([
            'title' => 'Read News',
            'is_read' => true,
        ]);

        Livewire::test(Index::class)
            ->set('readFilter', 'unread')
            ->assertSee('Unread News')
            ->assertDontSee('Read News');
    });

    it('can mark news as read', function () {
        $entity = TrackedEntity::factory()->create();
        $news = NewsItem::factory()->for($entity, 'trackedEntity')->create([
            'is_read' => false,
        ]);

        Livewire::test(Index::class)
            ->call('markAsRead', $news->id);

        expect($news->fresh()->is_read)->toBeTrue();
    });

    it('can dismiss news', function () {
        $entity = TrackedEntity::factory()->create();
        $news = NewsItem::factory()->for($entity, 'trackedEntity')->create([
            'is_relevant' => true,
        ]);

        Livewire::test(Index::class)
            ->call('dismiss', $news->id);

        expect($news->fresh()->is_relevant)->toBeFalse();
    });

    it('hides dismissed news by default', function () {
        $entity = TrackedEntity::factory()->create();
        NewsItem::factory()->for($entity, 'trackedEntity')->create([
            'title' => 'Visible News',
            'is_relevant' => true,
        ]);
        NewsItem::factory()->for($entity, 'trackedEntity')->create([
            'title' => 'Dismissed News',
            'is_relevant' => false,
        ]);

        Livewire::test(Index::class)
            ->assertSee('Visible News')
            ->assertDontSee('Dismissed News');
    });

    it('shows dismissed news when filter is set', function () {
        $entity = TrackedEntity::factory()->create();
        NewsItem::factory()->for($entity, 'trackedEntity')->create([
            'title' => 'Dismissed News',
            'is_relevant' => false,
        ]);

        Livewire::test(Index::class)
            ->set('showDismissed', true)
            ->assertSee('Dismissed News');
    });
});
