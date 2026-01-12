<?php

use App\Livewire\Settings\LinkedIn;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Settings LinkedIn', function () {
    it('renders the linkedin settings page', function () {
        $this->get(route('settings.linkedin'))
            ->assertSuccessful()
            ->assertSeeLivewire(LinkedIn::class);
    });

    it('loads default settings', function () {
        Livewire::test(LinkedIn::class)
            ->assertSet('linkedin_posts_enabled', true)
            ->assertSet('linkedin_post_frequency', 'weekly')
            ->assertSet('linkedin_posts_per_generation', 3)
            ->assertSet('linkedin_default_tone', 'professional')
            ->assertSet('linkedin_include_hashtags', true)
            ->assertSet('linkedin_include_cta', true);
    });

    it('loads existing user preferences', function () {
        $preferences = $this->user->getOrCreatePreferences();
        $preferences->update([
            'linkedin_posts_enabled' => false,
            'linkedin_post_frequency' => 'daily',
            'linkedin_posts_per_generation' => 5,
            'linkedin_default_tone' => 'conversational',
            'linkedin_topics' => ['industry_trends', 'company_updates'],
            'linkedin_include_hashtags' => false,
            'linkedin_include_cta' => false,
        ]);

        Livewire::test(LinkedIn::class)
            ->assertSet('linkedin_posts_enabled', false)
            ->assertSet('linkedin_post_frequency', 'daily')
            ->assertSet('linkedin_posts_per_generation', 5)
            ->assertSet('linkedin_default_tone', 'conversational')
            ->assertSet('linkedin_topics', ['industry_trends', 'company_updates'])
            ->assertSet('linkedin_include_hashtags', false)
            ->assertSet('linkedin_include_cta', false);
    });

    it('can save linkedin settings', function () {
        Livewire::test(LinkedIn::class)
            ->set('linkedin_posts_enabled', true)
            ->set('linkedin_post_frequency', 'twice_weekly')
            ->set('linkedin_posts_per_generation', 4)
            ->set('linkedin_default_tone', 'thought_leadership')
            ->set('linkedin_topics', ['industry_trends', 'thought_leadership'])
            ->set('linkedin_include_hashtags', false)
            ->set('linkedin_include_cta', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('settings-saved');

        $preferences = $this->user->fresh()->preferences;

        expect($preferences->linkedin_posts_enabled)->toBeTrue();
        expect($preferences->linkedin_post_frequency)->toBe('twice_weekly');
        expect($preferences->linkedin_posts_per_generation)->toBe(4);
        expect($preferences->linkedin_default_tone)->toBe('thought_leadership');
        expect($preferences->linkedin_topics)->toBe(['industry_trends', 'thought_leadership']);
        expect($preferences->linkedin_include_hashtags)->toBeFalse();
        expect($preferences->linkedin_include_cta)->toBeTrue();
    });

    it('validates frequency is valid option', function () {
        Livewire::test(LinkedIn::class)
            ->set('linkedin_post_frequency', 'invalid')
            ->call('save')
            ->assertHasErrors(['linkedin_post_frequency']);
    });

    it('validates posts per generation is between 1 and 5', function () {
        Livewire::test(LinkedIn::class)
            ->set('linkedin_posts_per_generation', 0)
            ->call('save')
            ->assertHasErrors(['linkedin_posts_per_generation']);

        Livewire::test(LinkedIn::class)
            ->set('linkedin_posts_per_generation', 10)
            ->call('save')
            ->assertHasErrors(['linkedin_posts_per_generation']);
    });

    it('validates tone is valid option', function () {
        Livewire::test(LinkedIn::class)
            ->set('linkedin_default_tone', 'invalid')
            ->call('save')
            ->assertHasErrors(['linkedin_default_tone']);
    });

    it('validates at least one topic is selected', function () {
        Livewire::test(LinkedIn::class)
            ->set('linkedin_topics', [])
            ->call('save')
            ->assertHasErrors(['linkedin_topics']);
    });

    it('validates topics are valid options', function () {
        Livewire::test(LinkedIn::class)
            ->set('linkedin_topics', ['invalid_topic'])
            ->call('save')
            ->assertHasErrors(['linkedin_topics.0']);
    });

    it('provides frequency options', function () {
        $component = Livewire::test(LinkedIn::class);

        expect($component->instance()->frequencies)->toHaveKeys(['daily', 'twice_weekly', 'weekly']);
    });

    it('provides tone options with descriptions', function () {
        $component = Livewire::test(LinkedIn::class);

        expect($component->instance()->tones)->toHaveKeys(['professional', 'conversational', 'thought_leadership', 'casual']);
        expect($component->instance()->tones['professional'])->toHaveKeys(['label', 'description']);
    });

    it('provides available topic options', function () {
        $component = Livewire::test(LinkedIn::class);

        expect($component->instance()->availableTopics)->toHaveKeys([
            'industry_trends',
            'thought_leadership',
            'news_commentary',
            'company_updates',
        ]);
    });
});
