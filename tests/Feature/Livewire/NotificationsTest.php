<?php

use App\Enums\EventCategory;
use App\Enums\EventSignificance;
use App\Enums\EventType;
use App\Enums\InsightPriority;
use App\Enums\InsightType;
use App\Livewire\Notifications\Bell;
use App\Livewire\Notifications\Index;
use App\Models\BusinessEvent;
use App\Models\ProactiveInsight;
use App\Models\User;
use App\Notifications\BusinessEventNotification;
use App\Notifications\ProactiveInsightNotification;
use App\Notifications\RunwayAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Notification Bell Component', function () {
    it('renders the bell component', function () {
        Livewire::actingAs($this->user)
            ->test(Bell::class)
            ->assertOk();
    });

    it('shows unread count when there are unread notifications', function () {
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 2,
            'threshold' => 3,
            'crossed_below' => false,
        ]));
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 1,
            'threshold' => 3,
            'crossed_below' => false,
        ]));

        Livewire::actingAs($this->user)
            ->test(Bell::class)
            ->assertSee('2');
    });

    it('shows 9+ when there are more than 9 unread notifications', function () {
        for ($i = 0; $i < 12; $i++) {
            $this->user->notify(new RunwayAlertNotification([
                'current_runway' => 2,
                'threshold' => 3,
                'crossed_below' => false,
            ]));
        }

        Livewire::actingAs($this->user)
            ->test(Bell::class)
            ->assertSee('9+');
    });

    it('can toggle dropdown', function () {
        Livewire::actingAs($this->user)
            ->test(Bell::class)
            ->assertSet('showDropdown', false)
            ->call('toggleDropdown')
            ->assertSet('showDropdown', true)
            ->call('toggleDropdown')
            ->assertSet('showDropdown', false);
    });

    it('shows recent notifications in dropdown', function () {
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 2,
            'threshold' => 3,
            'crossed_below' => false,
        ]));

        Livewire::actingAs($this->user)
            ->test(Bell::class)
            ->call('toggleDropdown')
            ->assertSee('Runway Alert');
    });

    it('can mark a notification as read', function () {
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 2,
            'threshold' => 3,
            'crossed_below' => false,
        ]));

        $notification = $this->user->unreadNotifications()->first();

        Livewire::actingAs($this->user)
            ->test(Bell::class)
            ->call('markAsRead', $notification->id)
            ->assertDispatched('notification-read');

        expect($this->user->unreadNotifications()->count())->toBe(0);
    });

    it('can mark all notifications as read', function () {
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 2,
            'threshold' => 3,
            'crossed_below' => false,
        ]));
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 1,
            'threshold' => 3,
            'crossed_below' => false,
        ]));

        expect($this->user->unreadNotifications()->count())->toBe(2);

        Livewire::actingAs($this->user)
            ->test(Bell::class)
            ->call('markAllAsRead')
            ->assertDispatched('all-notifications-read');

        expect($this->user->unreadNotifications()->count())->toBe(0);
    });
});

describe('Notifications Index Page', function () {
    it('can render notifications index page', function () {
        $this->actingAs($this->user)
            ->get('/notifications')
            ->assertOk()
            ->assertSeeLivewire(Index::class);
    });

    it('displays notifications', function () {
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 2,
            'threshold' => 3,
            'crossed_below' => false,
        ]));

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->assertSee('Runway Alert');
    });

    it('can filter by unread notifications', function () {
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 2,
            'threshold' => 3,
            'crossed_below' => false,
        ]));

        $notification = $this->user->notifications()->first();
        $notification->markAsRead();

        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 1,
            'threshold' => 3,
            'crossed_below' => false,
        ]));

        // Filter shows only unread
        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->set('filter', 'unread');

        // Should have 1 unread notification
        expect($this->user->unreadNotifications()->count())->toBe(1);
        expect($this->user->unreadNotifications()->first()->data['current_runway'])->toBe(1);
    });

    it('can filter by read notifications', function () {
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 2,
            'threshold' => 3,
            'crossed_below' => false,
        ]));

        $notification = $this->user->notifications()->first();
        $notification->markAsRead();

        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 1,
            'threshold' => 3,
            'crossed_below' => false,
        ]));

        // Filter shows only read
        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->set('filter', 'read');

        // Should have 1 read notification
        $readNotifications = $this->user->notifications()->whereNotNull('read_at')->get();
        expect($readNotifications->count())->toBe(1);
        expect($readNotifications->first()->data['current_runway'])->toBe(2);
    });

    it('can mark notification as read', function () {
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 2,
            'threshold' => 3,
            'crossed_below' => false,
        ]));

        $notification = $this->user->notifications()->first();

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('markAsRead', $notification->id);

        expect($this->user->unreadNotifications()->count())->toBe(0);
    });

    it('can mark notification as unread', function () {
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 2,
            'threshold' => 3,
            'crossed_below' => false,
        ]));

        $notification = $this->user->notifications()->first();
        $notification->markAsRead();

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('markAsUnread', $notification->id);

        expect($this->user->unreadNotifications()->count())->toBe(1);
    });

    it('can delete a notification', function () {
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 2,
            'threshold' => 3,
            'crossed_below' => false,
        ]));

        $notification = $this->user->notifications()->first();

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('delete', $notification->id);

        expect($this->user->notifications()->count())->toBe(0);
    });

    it('can mark all as read', function () {
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 2,
            'threshold' => 3,
            'crossed_below' => false,
        ]));
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 1,
            'threshold' => 3,
            'crossed_below' => false,
        ]));

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('markAllAsRead');

        expect($this->user->unreadNotifications()->count())->toBe(0);
    });

    it('can delete all notifications', function () {
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 2,
            'threshold' => 3,
            'crossed_below' => false,
        ]));
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 1,
            'threshold' => 3,
            'crossed_below' => false,
        ]));

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->call('deleteAll');

        expect($this->user->notifications()->count())->toBe(0);
    });

    it('shows empty state when no notifications', function () {
        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->assertSee('No notifications yet');
    });
});

describe('ProactiveInsightNotification', function () {
    it('creates database notification with correct data', function () {
        $insight = ProactiveInsight::factory()->for($this->user)->create([
            'title' => 'Test Insight',
            'content' => 'This is a test insight content.',
            'insight_type' => InsightType::Recommendation,
            'priority' => InsightPriority::High,
        ]);

        $this->user->notify(new ProactiveInsightNotification($insight));

        $notification = $this->user->notifications()->first();

        expect($notification->data['type'])->toBe('proactive_insight');
        expect($notification->data['title'])->toBe('Test Insight');
        expect($notification->data['insight_type'])->toBe('recommendation');
        expect($notification->data['priority'])->toBe('high');
    });

    it('only uses database channel', function () {
        $insight = ProactiveInsight::factory()->for($this->user)->create();
        $notification = new ProactiveInsightNotification($insight);

        expect($notification->via($this->user))->toBe(['database']);
    });
});

describe('BusinessEventNotification', function () {
    it('creates database notification with correct data', function () {
        $event = BusinessEvent::factory()->for($this->user)->create([
            'title' => 'Contract Signed',
            'description' => 'New contract signed with Acme Corp',
            'event_type' => EventType::ContractSigned,
            'category' => EventCategory::Financial,
            'significance' => EventSignificance::High,
        ]);

        $this->user->notify(new BusinessEventNotification($event));

        $notification = $this->user->notifications()->first();

        expect($notification->data['type'])->toBe('business_event');
        expect($notification->data['title'])->toBe('Contract Signed');
        expect($notification->data['event_type'])->toBe('contract_signed');
        expect($notification->data['category'])->toBe('financial');
        expect($notification->data['significance'])->toBe('high');
    });

    it('only uses database channel', function () {
        $event = BusinessEvent::factory()->for($this->user)->create();
        $notification = new BusinessEventNotification($event);

        expect($notification->via($this->user))->toBe(['database']);
    });
});

describe('RunwayAlertNotification', function () {
    it('creates database notification with correct data', function () {
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 2.5,
            'threshold' => 3,
            'crossed_below' => true,
            'monthly_burn' => 10000,
            'monthly_income' => 5000,
        ]));

        $notification = $this->user->notifications()->first();

        expect($notification->data['type'])->toBe('runway_alert');
        expect($notification->data['current_runway'])->toBe(2.5);
        expect($notification->data['threshold'])->toBe(3);
        expect($notification->data['crossed_below'])->toBe(true);
        expect($notification->data['monthly_burn'])->toBe(10000);
    });

    it('uses both mail and database channels when crossed below threshold', function () {
        $notification = new RunwayAlertNotification([
            'crossed_below' => true,
        ]);

        expect($notification->via($this->user))->toContain('database');
        expect($notification->via($this->user))->toContain('mail');
    });

    it('only uses database channel when not crossed below', function () {
        $notification = new RunwayAlertNotification([
            'crossed_below' => false,
        ]);

        expect($notification->via($this->user))->toBe(['database']);
    });

    it('generates correct email content', function () {
        $notification = new RunwayAlertNotification([
            'current_runway' => 2,
            'threshold' => 3,
            'crossed_below' => true,
        ]);

        $mailMessage = $notification->toMail($this->user);

        expect($mailMessage->subject)->toBe('Runway Alert - Immediate Attention Required');
    });
});

describe('Sidebar notification badge', function () {
    it('shows notification count in sidebar', function () {
        $this->user->notify(new RunwayAlertNotification([
            'current_runway' => 2,
            'threshold' => 3,
            'crossed_below' => false,
        ]));

        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertOk();

        expect($this->user->unreadNotifications()->count())->toBe(1);
    });
});
