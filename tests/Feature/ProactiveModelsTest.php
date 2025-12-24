<?php

use App\Enums\EventCategory;
use App\Enums\EventSignificance;
use App\Enums\EventType;
use App\Enums\InsightPriority;
use App\Enums\InsightType;
use App\Enums\TriggerType;
use App\Models\BusinessEvent;
use App\Models\DailyStandup;
use App\Models\ProactiveInsight;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('UserPreference Model', function () {
    it('can be created with valid data', function () {
        $preference = UserPreference::factory()->create([
            'standup_email_time' => '09:00',
            'standup_email_timezone' => 'America/New_York',
        ]);

        expect($preference)->toBeInstanceOf(UserPreference::class)
            ->and($preference->standup_email_time)->toBe('09:00')
            ->and($preference->standup_email_timezone)->toBe('America/New_York');
    });

    it('belongs to a user', function () {
        $user = User::factory()->create();
        $preference = UserPreference::factory()->for($user)->create();

        expect($preference->user->id)->toBe($user->id);
    });

    it('has correct default values', function () {
        $preference = UserPreference::factory()->create();

        expect($preference->standup_email_enabled)->toBeTrue()
            ->and($preference->in_app_notifications_enabled)->toBeTrue()
            ->and($preference->proactive_insights_enabled)->toBeTrue()
            ->and($preference->runway_alert_threshold)->toBe(3);
    });

    it('can detect when runway is within alert threshold', function () {
        $preference = UserPreference::factory()->withRunwayThreshold(3)->create();

        expect($preference->isWithinAlertThreshold(2.5))->toBeTrue()
            ->and($preference->isWithinAlertThreshold(5))->toBeFalse()
            ->and($preference->isWithinAlertThreshold(INF))->toBeFalse();
    });
});

describe('BusinessEvent Model', function () {
    it('can be created with valid data', function () {
        $event = BusinessEvent::factory()->create([
            'title' => 'New Contract Signed',
            'event_type' => EventType::ContractSigned,
            'category' => EventCategory::Financial,
        ]);

        expect($event)->toBeInstanceOf(BusinessEvent::class)
            ->and($event->title)->toBe('New Contract Signed')
            ->and($event->event_type)->toBe(EventType::ContractSigned)
            ->and($event->category)->toBe(EventCategory::Financial);
    });

    it('belongs to a user', function () {
        $user = User::factory()->create();
        $event = BusinessEvent::factory()->for($user)->create();

        expect($event->user->id)->toBe($user->id);
    });

    it('filters by category', function () {
        BusinessEvent::factory()->financial()->create();
        BusinessEvent::factory()->market()->create();
        BusinessEvent::factory()->advisory()->create();

        expect(BusinessEvent::financial()->count())->toBe(1)
            ->and(BusinessEvent::market()->count())->toBe(1)
            ->and(BusinessEvent::advisory()->count())->toBe(1);
    });

    it('filters recent events', function () {
        BusinessEvent::factory()->create(['occurred_at' => now()]);
        BusinessEvent::factory()->create(['occurred_at' => now()->subDays(10)]);

        expect(BusinessEvent::recent(7)->count())->toBe(1);
    });

    it('filters by significance', function () {
        BusinessEvent::factory()->low()->create();
        BusinessEvent::factory()->critical()->create();

        expect(BusinessEvent::bySignificance(EventSignificance::Critical)->count())->toBe(1);
    });

    it('filters high priority events', function () {
        BusinessEvent::factory()->low()->create();
        BusinessEvent::factory()->medium()->create();
        BusinessEvent::factory()->high()->create();
        BusinessEvent::factory()->critical()->create();

        expect(BusinessEvent::highPriority()->count())->toBe(2);
    });

    it('can identify high priority events', function () {
        $lowEvent = BusinessEvent::factory()->low()->create();
        $criticalEvent = BusinessEvent::factory()->critical()->create();

        expect($lowEvent->isHighPriority())->toBeFalse()
            ->and($criticalEvent->isHighPriority())->toBeTrue();
    });
});

describe('DailyStandup Model', function () {
    it('can be created with valid data', function () {
        $standup = DailyStandup::factory()->create([
            'standup_date' => '2025-01-15',
        ]);

        expect($standup)->toBeInstanceOf(DailyStandup::class)
            ->and($standup->standup_date->format('Y-m-d'))->toBe('2025-01-15');
    });

    it('belongs to a user', function () {
        $user = User::factory()->create();
        $standup = DailyStandup::factory()->for($user)->create();

        expect($standup->user->id)->toBe($user->id);
    });

    it('casts financial snapshot to array', function () {
        $standup = DailyStandup::factory()->create([
            'financial_snapshot' => ['monthly_income' => 10000],
        ]);

        expect($standup->financial_snapshot)->toBeArray()
            ->and($standup->financial_snapshot['monthly_income'])->toBe(10000);
    });

    it('can detect when it has alerts', function () {
        $withAlerts = DailyStandup::factory()->withAlerts()->create();
        $withoutAlerts = DailyStandup::factory()->create(['alerts' => []]);

        expect($withAlerts->hasAlerts())->toBeTrue()
            ->and($withoutAlerts->hasAlerts())->toBeFalse();
    });

    it('can be marked as viewed', function () {
        $standup = DailyStandup::factory()->create(['viewed_at' => null]);

        $standup->markAsViewed();

        expect($standup->fresh()->viewed_at)->not->toBeNull();
    });

    it('can be marked as email sent', function () {
        $standup = DailyStandup::factory()->create(['email_sent_at' => null]);

        $standup->markEmailSent();

        expect($standup->fresh()->email_sent_at)->not->toBeNull();
    });

    it('filters by date', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        DailyStandup::factory()->for($user1)->create(['standup_date' => now()->toDateString()]);
        DailyStandup::factory()->for($user2)->yesterday()->create();

        expect(DailyStandup::forDate(now())->count())->toBe(1);
    });

    it('filters unviewed standups', function () {
        DailyStandup::factory()->create(['viewed_at' => null]);
        DailyStandup::factory()->viewed()->create();

        expect(DailyStandup::unviewed()->count())->toBe(1);
    });

    it('formats snapshot correctly', function () {
        $standup = DailyStandup::factory()->create([
            'financial_snapshot' => [
                'monthly_income' => 10000.50,
                'monthly_expenses' => 5000.25,
                'monthly_net' => 5000.25,
                'runway_months' => 12.5,
                'contracts_count' => 3,
                'pipeline_count' => 2,
            ],
        ]);

        $formatted = $standup->getFormattedSnapshot();

        expect($formatted['monthly_income'])->toBe('10,000.50')
            ->and($formatted['runway_months'])->toBe('12.5 months')
            ->and($formatted['contracts_count'])->toBe(3);
    });
});

describe('ProactiveInsight Model', function () {
    it('can be created with valid data', function () {
        $insight = ProactiveInsight::factory()->create([
            'title' => 'Opportunity Identified',
            'insight_type' => InsightType::Opportunity,
            'priority' => InsightPriority::High,
        ]);

        expect($insight)->toBeInstanceOf(ProactiveInsight::class)
            ->and($insight->title)->toBe('Opportunity Identified')
            ->and($insight->insight_type)->toBe(InsightType::Opportunity)
            ->and($insight->priority)->toBe(InsightPriority::High);
    });

    it('belongs to a user', function () {
        $user = User::factory()->create();
        $insight = ProactiveInsight::factory()->for($user)->create();

        expect($insight->user->id)->toBe($user->id);
    });

    it('can be linked to a business event', function () {
        $event = BusinessEvent::factory()->create();
        $insight = ProactiveInsight::factory()->create([
            'related_event_id' => $event->id,
        ]);

        expect($insight->relatedEvent->id)->toBe($event->id);
    });

    it('filters unread insights', function () {
        ProactiveInsight::factory()->create(['is_read' => false]);
        ProactiveInsight::factory()->read()->create();

        expect(ProactiveInsight::unread()->count())->toBe(1);
    });

    it('filters active insights', function () {
        ProactiveInsight::factory()->create(['is_dismissed' => false]);
        ProactiveInsight::factory()->dismissed()->create();

        expect(ProactiveInsight::active()->count())->toBe(1);
    });

    it('filters urgent insights', function () {
        ProactiveInsight::factory()->urgent()->create();
        ProactiveInsight::factory()->low()->create();

        expect(ProactiveInsight::urgent()->count())->toBe(1);
    });

    it('filters high priority insights', function () {
        ProactiveInsight::factory()->low()->create();
        ProactiveInsight::factory()->medium()->create();
        ProactiveInsight::factory()->high()->create();
        ProactiveInsight::factory()->urgent()->create();

        expect(ProactiveInsight::highPriority()->count())->toBe(2);
    });

    it('can be marked as read', function () {
        $insight = ProactiveInsight::factory()->create(['is_read' => false]);

        $insight->markAsRead();

        expect($insight->fresh()->is_read)->toBeTrue();
    });

    it('can be dismissed', function () {
        $insight = ProactiveInsight::factory()->create(['is_dismissed' => false]);

        $insight->dismiss();

        expect($insight->fresh()->is_dismissed)->toBeTrue();
    });

    it('can identify actionable insights', function () {
        $actionable = ProactiveInsight::factory()->create([
            'is_read' => false,
            'is_dismissed' => false,
        ]);
        $read = ProactiveInsight::factory()->read()->create();

        expect($actionable->isActionable())->toBeTrue()
            ->and($read->isActionable())->toBeFalse();
    });

    it('filters by type', function () {
        ProactiveInsight::factory()->opportunity()->create();
        ProactiveInsight::factory()->warning()->create();

        expect(ProactiveInsight::byType(InsightType::Opportunity)->count())->toBe(1);
    });

    it('casts trigger type correctly', function () {
        $insight = ProactiveInsight::factory()->scheduled()->create();

        expect($insight->trigger_type)->toBe(TriggerType::Scheduled);
    });
});

describe('User Model Relationships', function () {
    it('can have preferences', function () {
        $user = User::factory()->create();
        $preference = UserPreference::factory()->for($user)->create();

        expect($user->preferences->id)->toBe($preference->id);
    });

    it('can have business events', function () {
        $user = User::factory()->create();
        BusinessEvent::factory()->count(3)->for($user)->create();

        expect($user->businessEvents)->toHaveCount(3);
    });

    it('can have daily standups', function () {
        $user = User::factory()->create();
        DailyStandup::factory()->for($user)->forDate(now())->create();
        DailyStandup::factory()->for($user)->forDate(now()->subDay())->create();

        expect($user->dailyStandups)->toHaveCount(2);
    });

    it('can have proactive insights', function () {
        $user = User::factory()->create();
        ProactiveInsight::factory()->count(4)->for($user)->create();

        expect($user->proactiveInsights)->toHaveCount(4);
    });

    it('can count unread insights', function () {
        $user = User::factory()->create();
        ProactiveInsight::factory()->count(2)->for($user)->create(['is_read' => false, 'is_dismissed' => false]);
        ProactiveInsight::factory()->for($user)->read()->create();
        ProactiveInsight::factory()->for($user)->dismissed()->create();

        expect($user->unreadInsightsCount())->toBe(2);
    });

    it('can get today\'s standup', function () {
        $user = User::factory()->create();
        $todayStandup = DailyStandup::factory()->for($user)->forDate(now())->create();
        DailyStandup::factory()->for($user)->forDate(now()->subDay())->create();

        expect($user->todaysStandup()->id)->toBe($todayStandup->id);
    });

    it('can get or create preferences', function () {
        $user = User::factory()->create();

        expect($user->preferences)->toBeNull();

        $preference = $user->getOrCreatePreferences();

        expect($preference)->toBeInstanceOf(UserPreference::class)
            ->and($user->fresh()->preferences)->not->toBeNull();
    });
});
