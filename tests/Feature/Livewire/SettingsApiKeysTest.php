<?php

use App\Livewire\Settings\ApiKeys;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Settings API Keys', function () {
    it('renders the api keys page', function () {
        $this->get(route('settings.api-keys'))
            ->assertSuccessful()
            ->assertSeeLivewire(ApiKeys::class);
    });

    it('loads existing settings', function () {
        Setting::set(Setting::KEY_SERPAPI_KEY, 'test-serpapi-key');
        Setting::set(Setting::KEY_COMPANY_NAME, 'Acme Corp');

        Livewire::test(ApiKeys::class)
            ->assertSet('serpapi_key', 'test-serpapi-key')
            ->assertSet('company_name', 'Acme Corp');
    });

    it('can save api keys', function () {
        Livewire::test(ApiKeys::class)
            ->set('anthropic_api_key', 'sk-ant-test123')
            ->set('openai_api_key', 'sk-openai-test456')
            ->set('serpapi_key', 'serpapi-test789')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('settings-saved');

        expect(Setting::get(Setting::KEY_ANTHROPIC_API_KEY))->toBe('sk-ant-test123');
        expect(Setting::get(Setting::KEY_OPENAI_API_KEY))->toBe('sk-openai-test456');
        expect(Setting::get(Setting::KEY_SERPAPI_KEY))->toBe('serpapi-test789');
    });

    it('can save company name', function () {
        Livewire::test(ApiKeys::class)
            ->set('company_name', 'My Consulting Firm')
            ->call('save')
            ->assertHasNoErrors();

        expect(Setting::get(Setting::KEY_COMPANY_NAME))->toBe('My Consulting Firm');
    });

    it('can set preferred llm provider', function () {
        Livewire::test(ApiKeys::class)
            ->set('preferred_llm_provider', 'openai')
            ->call('save')
            ->assertHasNoErrors();

        expect(Setting::get(Setting::KEY_PREFERRED_LLM_PROVIDER))->toBe('openai');
    });

    it('validates preferred provider is valid option', function () {
        Livewire::test(ApiKeys::class)
            ->set('preferred_llm_provider', 'invalid')
            ->call('save')
            ->assertHasErrors(['preferred_llm_provider']);
    });

    it('loads default news recency setting', function () {
        Livewire::test(ApiKeys::class)
            ->assertSet('news_recency', Setting::DEFAULT_NEWS_RECENCY);
    });

    it('loads existing news recency setting', function () {
        Setting::set(Setting::KEY_NEWS_RECENCY, 'd');

        Livewire::test(ApiKeys::class)
            ->assertSet('news_recency', 'd');
    });

    it('can save news recency setting', function () {
        Livewire::test(ApiKeys::class)
            ->set('news_recency', 'm')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('settings-saved');

        expect(Setting::get(Setting::KEY_NEWS_RECENCY))->toBe('m');
    });

    it('can save empty news recency for any time', function () {
        Livewire::test(ApiKeys::class)
            ->set('news_recency', '')
            ->call('save')
            ->assertHasNoErrors();

        expect(Setting::get(Setting::KEY_NEWS_RECENCY))->toBe('');
    });

    it('validates news recency is valid option', function () {
        Livewire::test(ApiKeys::class)
            ->set('news_recency', 'invalid')
            ->call('save')
            ->assertHasErrors(['news_recency']);
    });
});
