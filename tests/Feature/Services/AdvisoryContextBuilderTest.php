<?php

use App\Models\Setting;
use App\Services\AdvisoryContextBuilder;

test('build includes business profile when set', function () {
    Setting::set(Setting::KEY_COMPANY_NAME, 'Test Company');
    Setting::set(Setting::KEY_BUSINESS_INDUSTRY, 'Software Development');
    Setting::set(Setting::KEY_BUSINESS_DESCRIPTION, 'We build custom apps');
    Setting::set(Setting::KEY_BUSINESS_TARGET_MARKET, 'SMBs');
    Setting::set(Setting::KEY_BUSINESS_KEY_SERVICES, 'Web and Mobile');

    $builder = new AdvisoryContextBuilder();
    $context = $builder->build();

    expect($context)->toContain('BUSINESS PROFILE:');
    expect($context)->toContain('Industry: Software Development');
    expect($context)->toContain('Description: We build custom apps');
    expect($context)->toContain('Target Market: SMBs');
    expect($context)->toContain('Key Services: Web and Mobile');
});

test('build excludes business profile when not set', function () {
    Setting::remove(Setting::KEY_BUSINESS_INDUSTRY);
    Setting::remove(Setting::KEY_BUSINESS_DESCRIPTION);
    Setting::remove(Setting::KEY_BUSINESS_TARGET_MARKET);
    Setting::remove(Setting::KEY_BUSINESS_KEY_SERVICES);

    $builder = new AdvisoryContextBuilder();
    $context = $builder->build();

    expect($context)->not->toContain('BUSINESS PROFILE:');
});

test('snapshot includes business profile fields', function () {
    Setting::set(Setting::KEY_BUSINESS_INDUSTRY, 'Consulting');
    Setting::set(Setting::KEY_BUSINESS_DESCRIPTION, 'Business advisory');

    $builder = new AdvisoryContextBuilder();
    $snapshot = $builder->snapshot();

    expect($snapshot)->toHaveKey('business_industry', 'Consulting');
    expect($snapshot)->toHaveKey('business_description', 'Business advisory');
});
