<?php

use App\Livewire\Settings\BusinessProfile;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

test('business profile page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/settings/business-profile')->assertOk();
});

test('business profile page requires authentication', function () {
    $this->get('/settings/business-profile')
        ->assertRedirect('/login');
});

test('business profile can be saved', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BusinessProfile::class)
        ->set('business_industry', 'Software Development')
        ->set('business_description', 'We build custom software solutions')
        ->set('business_target_market', 'Small to medium businesses')
        ->set('business_key_services', 'Web development, Mobile apps, Consulting')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('settings-saved');

    expect(Setting::get(Setting::KEY_BUSINESS_INDUSTRY))->toBe('Software Development');
    expect(Setting::get(Setting::KEY_BUSINESS_DESCRIPTION))->toBe('We build custom software solutions');
    expect(Setting::get(Setting::KEY_BUSINESS_TARGET_MARKET))->toBe('Small to medium businesses');
    expect(Setting::get(Setting::KEY_BUSINESS_KEY_SERVICES))->toBe('Web development, Mobile apps, Consulting');
});

test('business profile loads existing values', function () {
    $this->actingAs(User::factory()->create());

    Setting::set(Setting::KEY_BUSINESS_INDUSTRY, 'Marketing');
    Setting::set(Setting::KEY_BUSINESS_DESCRIPTION, 'Digital marketing agency');
    Setting::set(Setting::KEY_BUSINESS_TARGET_MARKET, 'E-commerce brands');
    Setting::set(Setting::KEY_BUSINESS_KEY_SERVICES, 'SEO, PPC, Social Media');

    Livewire::test(BusinessProfile::class)
        ->assertSet('business_industry', 'Marketing')
        ->assertSet('business_description', 'Digital marketing agency')
        ->assertSet('business_target_market', 'E-commerce brands')
        ->assertSet('business_key_services', 'SEO, PPC, Social Media');
});

test('business profile fields are optional', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BusinessProfile::class)
        ->set('business_industry', '')
        ->set('business_description', '')
        ->set('business_target_market', '')
        ->set('business_key_services', '')
        ->call('save')
        ->assertHasNoErrors();
});

test('business profile validates max lengths', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(BusinessProfile::class)
        ->set('business_industry', str_repeat('a', 256))
        ->call('save')
        ->assertHasErrors(['business_industry']);
});
