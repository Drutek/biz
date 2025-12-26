<?php

use App\Livewire\Standup\InteractiveEntry;
use App\Livewire\Standup\Today;
use App\Models\DailyStandup;
use App\Models\StandupEntry;
use App\Models\User;
use App\Models\UserPreference;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->preferences = UserPreference::create([
        'user_id' => $this->user->id,
        'standup_email_enabled' => false,
        'standup_email_time' => '07:00',
        'standup_email_timezone' => 'UTC',
        'in_app_notifications_enabled' => true,
        'proactive_insights_enabled' => true,
        'runway_alert_threshold' => 3,
        'weekends_are_workdays' => false,
        'task_suggestions_enabled' => true,
        'overdue_reminders_enabled' => true,
        'overdue_reminder_time' => '09:00',
        'interactive_standup_enabled' => true,
    ]);
});

describe('StandupEntry Model', function () {
    it('can create a standup entry', function () {
        $standup = DailyStandup::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $entry = StandupEntry::factory()->create([
            'user_id' => $this->user->id,
            'daily_standup_id' => $standup->id,
            'yesterday_accomplished' => 'Finished project A',
            'today_planned' => 'Start project B',
            'blockers' => null,
        ]);

        expect($entry->yesterday_accomplished)->toBe('Finished project A')
            ->and($entry->today_planned)->toBe('Start project B')
            ->and($entry->blockers)->toBeNull();
    });

    it('can check if entry is complete', function () {
        $standup1 = DailyStandup::factory()->create([
            'user_id' => $this->user->id,
            'standup_date' => now()->subDay(),
        ]);

        $standup2 = DailyStandup::factory()->create([
            'user_id' => $this->user->id,
            'standup_date' => now(),
        ]);

        $incomplete = StandupEntry::factory()->notSubmitted()->create([
            'user_id' => $this->user->id,
            'daily_standup_id' => $standup1->id,
        ]);

        $complete = StandupEntry::factory()->create([
            'user_id' => $this->user->id,
            'daily_standup_id' => $standup2->id,
            'submitted_at' => now(),
        ]);

        expect($incomplete->isComplete())->toBeFalse()
            ->and($complete->isComplete())->toBeTrue();
    });
});

describe('UserPreference Work Day', function () {
    it('identifies weekdays as work days', function () {
        // Monday
        $monday = now()->startOfWeek();

        expect($this->preferences->isWorkDay($monday))->toBeTrue();
    });

    it('identifies weekends as non-work days by default', function () {
        // Saturday
        $saturday = now()->startOfWeek()->addDays(5);

        expect($this->preferences->isWorkDay($saturday))->toBeFalse();
    });

    it('identifies weekends as work days when enabled', function () {
        $this->preferences->update(['weekends_are_workdays' => true]);

        $saturday = now()->startOfWeek()->addDays(5);

        expect($this->preferences->isWorkDay($saturday))->toBeTrue();
    });
});

describe('Today Component', function () {
    it('renders the standup page', function () {
        Livewire::test(Today::class)
            ->assertStatus(200)
            ->assertSeeHtml("Today's Briefing");
    });

    it('shows interactive entry on work days', function () {
        // Ensure it's a weekday for the test
        $this->travel(fn () => now()->startOfWeek()->addDay());

        Livewire::test(Today::class)
            ->assertSet('isWorkDay', true)
            ->assertSet('interactiveStandupEnabled', true);
    });

    it('respects interactive standup enabled setting', function () {
        $this->preferences->update(['interactive_standup_enabled' => false]);

        Livewire::test(Today::class)
            ->assertSet('interactiveStandupEnabled', false);
    });
});

describe('InteractiveEntry Component', function () {
    it('renders the form when not submitted', function () {
        $standup = DailyStandup::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Livewire::test(InteractiveEntry::class, ['standup' => $standup])
            ->assertSee('Daily Check-in')
            ->assertSee('What did you accomplish yesterday?');
    });

    it('can skip the entry', function () {
        $standup = DailyStandup::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Livewire::test(InteractiveEntry::class, ['standup' => $standup])
            ->call('skip')
            ->assertSet('isSkipped', true)
            ->assertSee('Check-in skipped');
    });

    it('shows completed entry when already submitted', function () {
        $standup = DailyStandup::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $entry = StandupEntry::factory()->create([
            'user_id' => $this->user->id,
            'daily_standup_id' => $standup->id,
            'yesterday_accomplished' => 'Did something',
            'today_planned' => 'Will do something',
            'submitted_at' => now(),
        ]);

        expect($entry->isComplete())->toBeTrue();

        Livewire::test(InteractiveEntry::class, ['standup' => $standup])
            ->assertSet('entry.id', $entry->id)
            ->assertSeeHtml("Today's Check-in Complete")
            ->assertSeeHtml('Did something');
    });
});

describe('Standup Route', function () {
    it('requires authentication', function () {
        auth()->logout();

        $this->get('/today')
            ->assertRedirect('/login');
    });

    it('is accessible when authenticated', function () {
        $this->get('/today')
            ->assertStatus(200);
    });
});
