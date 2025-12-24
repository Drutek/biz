<?php

use App\Livewire\Contracts\Index as ContractsIndex;
use App\Livewire\Dashboard;
use App\Livewire\Expenses\Index as ExpensesIndex;
use App\Livewire\Advisor\Chat as AdvisorChat;
use App\Livewire\News\Index as NewsIndex;
use App\Livewire\TrackedEntities\Index as TrackedEntitiesIndex;
use App\Livewire\Settings\ApiKeys;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\BusinessProfile;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('dashboard', Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('contracts', ContractsIndex::class)->name('contracts.index');
    Route::get('expenses', ExpensesIndex::class)->name('expenses.index');
    Route::get('tracked-entities', TrackedEntitiesIndex::class)->name('tracked-entities.index');
    Route::get('news', NewsIndex::class)->name('news.index');
    Route::get('advisor', AdvisorChat::class)->name('advisor.index');

    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');
    Route::get('settings/api-keys', ApiKeys::class)->name('settings.api-keys');
    Route::get('settings/business-profile', BusinessProfile::class)->name('settings.business-profile');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
