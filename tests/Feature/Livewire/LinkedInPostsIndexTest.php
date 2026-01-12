<?php

use App\Enums\LinkedInPostType;
use App\Livewire\LinkedInPosts\Index;
use App\Models\LinkedInPost;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('LinkedIn Posts Index', function () {
    it('renders the linkedin posts page', function () {
        $this->get(route('linkedin.index'))
            ->assertSuccessful()
            ->assertSeeLivewire(Index::class);
    });

    it('shows empty state when no posts exist', function () {
        Livewire::test(Index::class)
            ->assertSee('No posts yet')
            ->assertSee('Generate Posts');
    });

    it('displays existing posts', function () {
        LinkedInPost::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'title' => 'Test LinkedIn Post',
        ]);

        Livewire::test(Index::class)
            ->assertSee('Test LinkedIn Post')
            ->assertDontSee('No posts yet');
    });

    it('can filter by post type', function () {
        LinkedInPost::factory()->newsCommentary()->create([
            'user_id' => $this->user->id,
            'title' => 'News Post',
        ]);
        LinkedInPost::factory()->thoughtLeadership()->create([
            'user_id' => $this->user->id,
            'title' => 'Thought Post',
        ]);

        Livewire::test(Index::class)
            ->set('typeFilter', 'news_commentary')
            ->assertSee('News Post')
            ->assertDontSee('Thought Post');
    });

    it('can filter by status', function () {
        LinkedInPost::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Active Post',
            'is_used' => false,
            'is_dismissed' => false,
        ]);
        LinkedInPost::factory()->used()->create([
            'user_id' => $this->user->id,
            'title' => 'Used Post',
        ]);
        LinkedInPost::factory()->dismissed()->create([
            'user_id' => $this->user->id,
            'title' => 'Dismissed Post',
        ]);

        Livewire::test(Index::class)
            ->set('statusFilter', 'active')
            ->assertSee('Active Post')
            ->assertDontSee('Dismissed Post');

        Livewire::test(Index::class)
            ->set('statusFilter', 'used')
            ->assertSee('Used Post')
            ->assertDontSee('Active Post');

        Livewire::test(Index::class)
            ->set('statusFilter', 'dismissed')
            ->assertSee('Dismissed Post')
            ->assertDontSee('Active Post');
    });

    it('can mark post as used', function () {
        $post = LinkedInPost::factory()->create([
            'user_id' => $this->user->id,
            'is_used' => false,
        ]);

        Livewire::test(Index::class)
            ->call('markAsUsed', $post->id);

        expect($post->fresh()->is_used)->toBeTrue();
    });

    it('can dismiss a post', function () {
        $post = LinkedInPost::factory()->create([
            'user_id' => $this->user->id,
            'is_dismissed' => false,
        ]);

        Livewire::test(Index::class)
            ->call('dismiss', $post->id);

        expect($post->fresh()->is_dismissed)->toBeTrue();
    });

    it('cannot mark another users post as used', function () {
        $otherUser = User::factory()->create();
        $post = LinkedInPost::factory()->create([
            'user_id' => $otherUser->id,
            'is_used' => false,
        ]);

        Livewire::test(Index::class)
            ->call('markAsUsed', $post->id);

        expect($post->fresh()->is_used)->toBeFalse();
    });

    it('cannot dismiss another users post', function () {
        $otherUser = User::factory()->create();
        $post = LinkedInPost::factory()->create([
            'user_id' => $otherUser->id,
            'is_dismissed' => false,
        ]);

        Livewire::test(Index::class)
            ->call('dismiss', $post->id);

        expect($post->fresh()->is_dismissed)->toBeFalse();
    });

    it('resets pagination when changing type filter', function () {
        LinkedInPost::factory()->count(20)->create(['user_id' => $this->user->id]);

        Livewire::test(Index::class)
            ->set('typeFilter', 'news_commentary')
            ->assertSuccessful();
    });

    it('resets pagination when changing status filter', function () {
        LinkedInPost::factory()->count(20)->create(['user_id' => $this->user->id]);

        Livewire::test(Index::class)
            ->set('statusFilter', 'used')
            ->assertSuccessful();
    });

    it('shows post types in badge', function () {
        LinkedInPost::factory()->newsCommentary()->create([
            'user_id' => $this->user->id,
        ]);

        Livewire::test(Index::class)
            ->assertSee('News Commentary');
    });

    it('shows used badge for used posts', function () {
        LinkedInPost::factory()->used()->create([
            'user_id' => $this->user->id,
        ]);

        Livewire::test(Index::class)
            ->set('statusFilter', 'used')
            ->assertSee('Used');
    });

    it('only shows own posts', function () {
        $otherUser = User::factory()->create();

        LinkedInPost::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'My Post',
        ]);
        LinkedInPost::factory()->create([
            'user_id' => $otherUser->id,
            'title' => 'Other Post',
        ]);

        Livewire::test(Index::class)
            ->assertSee('My Post')
            ->assertDontSee('Other Post');
    });

    it('dispatches copy event when copying to clipboard', function () {
        $post = LinkedInPost::factory()->create([
            'user_id' => $this->user->id,
            'content' => 'Test content',
            'hashtags' => ['test', 'linkedin'],
        ]);

        Livewire::test(Index::class)
            ->call('copyToClipboard', $post->id)
            ->assertDispatched('copy-to-clipboard');
    });

    it('provides post types for filter', function () {
        $component = Livewire::test(Index::class);

        $postTypes = $component->viewData('postTypes');

        expect($postTypes)->toContain(LinkedInPostType::NewsCommentary);
        expect($postTypes)->toContain(LinkedInPostType::ThoughtLeadership);
    });
});
