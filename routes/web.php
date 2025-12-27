<?php

use App\Livewire\Advisor\Chat as AdvisorChat;
use App\Livewire\Contracts\Index as ContractsIndex;
use App\Livewire\Dashboard;
use App\Livewire\Events\Create as EventsCreate;
use App\Livewire\Events\Index as EventsIndex;
use App\Livewire\Expenses\Index as ExpensesIndex;
use App\Livewire\Insights\Index as InsightsIndex;
use App\Livewire\News\Index as NewsIndex;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Livewire\Products\Index as ProductsIndex;
use App\Livewire\Products\Show as ProductsShow;
use App\Livewire\Settings\ApiKeys;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\BusinessProfile;
use App\Livewire\Settings\Notifications;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TaskIntegrations;
use App\Livewire\Settings\TwoFactor;
use App\Livewire\Standup\Archive as StandupArchive;
use App\Livewire\Standup\Today as StandupToday;
use App\Livewire\Tasks\Index as TasksIndex;
use App\Livewire\TrackedEntities\Index as TrackedEntitiesIndex;
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
    Route::get('products', ProductsIndex::class)->name('products.index');
    Route::get('products/{product}', ProductsShow::class)->name('products.show');
    Route::get('tracked-entities', TrackedEntitiesIndex::class)->name('tracked-entities.index');
    Route::get('news', NewsIndex::class)->name('news.index');
    Route::get('notifications', NotificationsIndex::class)->name('notifications.index');
    Route::get('advisor', AdvisorChat::class)->name('advisor.index');

    Route::get('today', StandupToday::class)->name('standup.today');
    Route::get('standup/archive', StandupArchive::class)->name('standup.archive');
    Route::get('events', EventsIndex::class)->name('events.index');
    Route::get('events/create', EventsCreate::class)->name('events.create');
    Route::get('insights', InsightsIndex::class)->name('insights.index');
    Route::get('tasks', TasksIndex::class)->name('tasks.index');

    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');
    Route::get('settings/api-keys', ApiKeys::class)->name('settings.api-keys');
    Route::get('settings/business-profile', BusinessProfile::class)->name('settings.business-profile');
    Route::get('settings/notifications', Notifications::class)->name('settings.notifications');
    Route::get('settings/integrations', TaskIntegrations::class)->name('settings.integrations');

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
