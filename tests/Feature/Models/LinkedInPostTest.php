<?php

use App\Enums\LinkedInPostType;
use App\Enums\LinkedInTone;
use App\Models\LinkedInPost;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('LinkedInPost Model', function () {
    it('can be created with factory', function () {
        $post = LinkedInPost::factory()->create(['user_id' => $this->user->id]);

        expect($post)->toBeInstanceOf(LinkedInPost::class);
        expect($post->user_id)->toBe($this->user->id);
        expect($post->post_type)->toBeInstanceOf(LinkedInPostType::class);
        expect($post->tone)->toBeInstanceOf(LinkedInTone::class);
    });

    it('belongs to a user', function () {
        $post = LinkedInPost::factory()->create(['user_id' => $this->user->id]);

        expect($post->user)->toBeInstanceOf(User::class);
        expect($post->user->id)->toBe($this->user->id);
    });

    it('casts hashtags to array', function () {
        $post = LinkedInPost::factory()->create([
            'user_id' => $this->user->id,
            'hashtags' => ['tech', 'business', 'ai'],
        ]);

        expect($post->hashtags)->toBeArray();
        expect($post->hashtags)->toContain('tech');
    });

    it('has scope for unused posts', function () {
        LinkedInPost::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'is_used' => false,
            'is_dismissed' => false,
        ]);
        LinkedInPost::factory()->create([
            'user_id' => $this->user->id,
            'is_used' => true,
        ]);
        LinkedInPost::factory()->create([
            'user_id' => $this->user->id,
            'is_dismissed' => true,
        ]);

        $unused = LinkedInPost::unused()->get();

        expect($unused)->toHaveCount(2);
    });

    it('has scope for active posts', function () {
        LinkedInPost::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'is_dismissed' => false,
        ]);
        LinkedInPost::factory()->create([
            'user_id' => $this->user->id,
            'is_dismissed' => true,
        ]);

        $active = LinkedInPost::active()->get();

        expect($active)->toHaveCount(3);
    });

    it('has scope for post type', function () {
        LinkedInPost::factory()->newsCommentary()->create(['user_id' => $this->user->id]);
        LinkedInPost::factory()->thoughtLeadership()->count(2)->create(['user_id' => $this->user->id]);

        $newsCommentary = LinkedInPost::forType(LinkedInPostType::NewsCommentary)->get();
        $thoughtLeadership = LinkedInPost::forType(LinkedInPostType::ThoughtLeadership)->get();

        expect($newsCommentary)->toHaveCount(1);
        expect($thoughtLeadership)->toHaveCount(2);
    });

    it('has scope for recent posts', function () {
        LinkedInPost::factory()->create([
            'user_id' => $this->user->id,
            'generated_at' => now()->subDays(3),
        ]);
        LinkedInPost::factory()->create([
            'user_id' => $this->user->id,
            'generated_at' => now()->subDays(10),
        ]);

        $recent = LinkedInPost::recent(7)->get();

        expect($recent)->toHaveCount(1);
    });

    it('can be marked as used', function () {
        $post = LinkedInPost::factory()->create([
            'user_id' => $this->user->id,
            'is_used' => false,
        ]);

        $post->markAsUsed();

        expect($post->fresh()->is_used)->toBeTrue();
    });

    it('can be dismissed', function () {
        $post = LinkedInPost::factory()->create([
            'user_id' => $this->user->id,
            'is_dismissed' => false,
        ]);

        $post->dismiss();

        expect($post->fresh()->is_dismissed)->toBeTrue();
    });

    it('can use factory states for different types', function () {
        $newsPost = LinkedInPost::factory()->newsCommentary()->create(['user_id' => $this->user->id]);
        $thoughtPost = LinkedInPost::factory()->thoughtLeadership()->create(['user_id' => $this->user->id]);
        $insightPost = LinkedInPost::factory()->industryInsight()->create(['user_id' => $this->user->id]);
        $updatePost = LinkedInPost::factory()->companyUpdate()->create(['user_id' => $this->user->id]);

        expect($newsPost->post_type)->toBe(LinkedInPostType::NewsCommentary);
        expect($thoughtPost->post_type)->toBe(LinkedInPostType::ThoughtLeadership);
        expect($insightPost->post_type)->toBe(LinkedInPostType::IndustryInsight);
        expect($updatePost->post_type)->toBe(LinkedInPostType::CompanyUpdate);
    });

    it('can use factory states for tones', function () {
        $professional = LinkedInPost::factory()->professional()->create(['user_id' => $this->user->id]);
        $conversational = LinkedInPost::factory()->conversational()->create(['user_id' => $this->user->id]);

        expect($professional->tone)->toBe(LinkedInTone::Professional);
        expect($conversational->tone)->toBe(LinkedInTone::Conversational);
    });

    it('can use factory states for used and dismissed', function () {
        $used = LinkedInPost::factory()->used()->create(['user_id' => $this->user->id]);
        $dismissed = LinkedInPost::factory()->dismissed()->create(['user_id' => $this->user->id]);

        expect($used->is_used)->toBeTrue();
        expect($dismissed->is_dismissed)->toBeTrue();
    });
});
