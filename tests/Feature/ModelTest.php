<?php

use App\Enums\EntityType;
use App\Enums\LLMProvider;
use App\Enums\MessageRole;
use App\Models\AdvisoryMessage;
use App\Models\AdvisoryThread;
use App\Models\NewsItem;
use App\Models\Setting;
use App\Models\TrackedEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('TrackedEntity Model', function () {
    it('can be created with valid data', function () {
        $entity = TrackedEntity::factory()->create([
            'name' => 'Pfizer',
            'entity_type' => EntityType::Company,
            'search_query' => 'Pfizer pharmaceutical news',
        ]);

        expect($entity)->toBeInstanceOf(TrackedEntity::class)
            ->and($entity->name)->toBe('Pfizer')
            ->and($entity->entity_type)->toBe(EntityType::Company);
    });

    it('has many news items', function () {
        $entity = TrackedEntity::factory()->create();
        NewsItem::factory()->count(3)->forEntity($entity)->create();

        expect($entity->newsItems)->toHaveCount(3);
    });

    it('filters active entities', function () {
        TrackedEntity::factory()->create(['is_active' => true]);
        TrackedEntity::factory()->create(['is_active' => false]);

        expect(TrackedEntity::active()->count())->toBe(1);
    });
});

describe('NewsItem Model', function () {
    it('can be created with valid data', function () {
        $newsItem = NewsItem::factory()->create([
            'title' => 'Breaking News',
            'source' => 'Reuters',
        ]);

        expect($newsItem)->toBeInstanceOf(NewsItem::class)
            ->and($newsItem->title)->toBe('Breaking News')
            ->and($newsItem->source)->toBe('Reuters');
    });

    it('belongs to a tracked entity', function () {
        $entity = TrackedEntity::factory()->create();
        $newsItem = NewsItem::factory()->forEntity($entity)->create();

        expect($newsItem->trackedEntity->id)->toBe($entity->id);
    });

    it('can be marked as read', function () {
        $newsItem = NewsItem::factory()->create(['is_read' => false]);

        $newsItem->markAsRead();

        expect($newsItem->fresh()->is_read)->toBeTrue();
    });

    it('can be dismissed', function () {
        $newsItem = NewsItem::factory()->create(['is_relevant' => true]);

        $newsItem->dismiss();

        expect($newsItem->fresh()->is_relevant)->toBeFalse();
    });

    it('filters unread items', function () {
        NewsItem::factory()->create(['is_read' => false]);
        NewsItem::factory()->read()->create();

        expect(NewsItem::unread()->count())->toBe(1);
    });

    it('filters relevant items', function () {
        NewsItem::factory()->create(['is_relevant' => true]);
        NewsItem::factory()->dismissed()->create();

        expect(NewsItem::relevant()->count())->toBe(1);
    });
});

describe('AdvisoryThread Model', function () {
    it('can be created with valid data', function () {
        $thread = AdvisoryThread::factory()->create([
            'title' => 'Strategic Planning Discussion',
        ]);

        expect($thread)->toBeInstanceOf(AdvisoryThread::class)
            ->and($thread->title)->toBe('Strategic Planning Discussion');
    });

    it('has many messages', function () {
        $thread = AdvisoryThread::factory()->create();
        AdvisoryMessage::factory()->count(3)->inThread($thread)->create();

        expect($thread->messages)->toHaveCount(3);
    });

    it('can add user message', function () {
        $thread = AdvisoryThread::factory()->create();

        $message = $thread->addUserMessage('What should I do about cashflow?');

        expect($message->role)->toBe(MessageRole::User)
            ->and($message->content)->toBe('What should I do about cashflow?');
    });

    it('can add assistant message', function () {
        $thread = AdvisoryThread::factory()->create();

        $message = $thread->addAssistantMessage(
            'Based on your financials...',
            'claude',
            'claude-sonnet-4-20250514',
            500
        );

        expect($message->role)->toBe(MessageRole::Assistant)
            ->and($message->provider)->toBe(LLMProvider::Claude)
            ->and($message->tokens_used)->toBe(500);
    });

    it('casts context snapshot to array', function () {
        $thread = AdvisoryThread::factory()->create([
            'context_snapshot' => ['monthly_income' => 5000],
        ]);

        expect($thread->context_snapshot)->toBeArray()
            ->and($thread->context_snapshot['monthly_income'])->toBe(5000);
    });
});

describe('AdvisoryMessage Model', function () {
    it('can be created with valid data', function () {
        $message = AdvisoryMessage::factory()->create([
            'content' => 'Hello, I need advice.',
            'role' => MessageRole::User,
        ]);

        expect($message)->toBeInstanceOf(AdvisoryMessage::class)
            ->and($message->content)->toBe('Hello, I need advice.')
            ->and($message->role)->toBe(MessageRole::User);
    });

    it('belongs to a thread', function () {
        $thread = AdvisoryThread::factory()->create();
        $message = AdvisoryMessage::factory()->inThread($thread)->create();

        expect($message->thread->id)->toBe($thread->id);
    });

    it('can identify user messages', function () {
        $message = AdvisoryMessage::factory()->fromUser()->create();

        expect($message->isFromUser())->toBeTrue()
            ->and($message->isFromAssistant())->toBeFalse();
    });

    it('can identify assistant messages', function () {
        $message = AdvisoryMessage::factory()->fromAssistant()->create();

        expect($message->isFromAssistant())->toBeTrue()
            ->and($message->isFromUser())->toBeFalse();
    });
});

describe('Setting Model', function () {
    it('can get and set values', function () {
        Setting::set('test_key', 'test_value');

        expect(Setting::get('test_key'))->toBe('test_value');
    });

    it('returns default when key not found', function () {
        expect(Setting::get('nonexistent', 'default'))->toBe('default');
    });

    it('can remove settings', function () {
        Setting::set('temp_key', 'temp_value');
        Setting::remove('temp_key');

        expect(Setting::get('temp_key'))->toBeNull();
    });

    it('updates existing settings', function () {
        Setting::set('update_key', 'original');
        Setting::set('update_key', 'updated');

        expect(Setting::get('update_key'))->toBe('updated');
        expect(Setting::query()->where('key', 'update_key')->count())->toBe(1);
    });
});
