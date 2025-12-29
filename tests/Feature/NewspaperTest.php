<?php

use App\Jobs\GenerateNewspaperJob;
use App\Livewire\News\Newspaper;
use App\Models\NewsItem;
use App\Models\NewspaperEdition;
use App\Models\TrackedEntity;
use App\Models\User;
use App\Services\LLM\LLMManager;
use App\Services\LLM\LLMResponse;
use App\Services\News\NewspaperService;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

describe('NewspaperEdition Model', function () {
    it('has correct fillable attributes', function () {
        $edition = NewspaperEdition::factory()->create();

        expect($edition->user_id)->not->toBeNull()
            ->and($edition->edition_date)->not->toBeNull()
            ->and($edition->headline)->not->toBeNull()
            ->and($edition->summary)->not->toBeNull()
            ->and($edition->sections)->toBeArray()
            ->and($edition->generated_at)->not->toBeNull();
    });

    it('casts sections to array', function () {
        $edition = NewspaperEdition::factory()->create();

        expect($edition->sections)->toBeArray();
    });

    it('belongs to a user', function () {
        $user = User::factory()->create();
        $edition = NewspaperEdition::factory()->create(['user_id' => $user->id]);

        expect($edition->user->id)->toBe($user->id);
    });

    it('can find todays edition for user', function () {
        $user = User::factory()->create();
        NewspaperEdition::factory()->create([
            'user_id' => $user->id,
            'edition_date' => now()->toDateString(),
        ]);

        $edition = NewspaperEdition::todayForUser($user);

        expect($edition)->not->toBeNull()
            ->and($edition->user_id)->toBe($user->id);
    });

    it('returns null when no edition exists for today', function () {
        $user = User::factory()->create();

        $edition = NewspaperEdition::todayForUser($user);

        expect($edition)->toBeNull();
    });
});

describe('NewspaperService', function () {
    it('returns null when no news items exist', function () {
        $user = User::factory()->create();
        $service = app(NewspaperService::class);

        $result = $service->generateForUser($user);

        expect($result)->toBeNull();
    });

    it('parses valid JSON response', function () {
        $user = User::factory()->create();
        $entity = TrackedEntity::factory()->create(['is_active' => true]);
        NewsItem::factory()->count(3)->create([
            'tracked_entity_id' => $entity->id,
            'fetched_at' => now(),
        ]);

        $mockResponse = new LLMResponse(
            content: json_encode([
                'headline' => 'Test Headline',
                'summary' => 'Test summary paragraph.',
                'sections' => [
                    [
                        'title' => 'Market News',
                        'icon' => 'chart-bar',
                        'articles' => [],
                    ],
                ],
            ]),
            provider: 'claude',
            model: 'test',
            tokensUsed: 100
        );

        $mockLLM = mock(LLMManager::class);
        $mockProvider = mock(\App\Services\LLM\LLMProvider::class);
        $mockProvider->shouldReceive('chat')->andReturn($mockResponse);
        $mockLLM->shouldReceive('driver')->andReturn($mockProvider);

        app()->instance(LLMManager::class, $mockLLM);

        $service = app(NewspaperService::class);
        $result = $service->generateForUser($user);

        expect($result)->toBeInstanceOf(NewspaperEdition::class)
            ->and($result->headline)->toBe('Test Headline')
            ->and($result->summary)->toBe('Test summary paragraph.');
    });
});

describe('GenerateNewspaperJob', function () {
    it('can be dispatched to queue', function () {
        Queue::fake();

        GenerateNewspaperJob::dispatch();

        Queue::assertPushed(GenerateNewspaperJob::class);
    });

    it('skips generation when no tracked entities exist', function () {
        TrackedEntity::query()->delete();

        $mockService = mock(NewspaperService::class);
        $mockService->shouldNotReceive('generateForUser');

        app()->instance(NewspaperService::class, $mockService);

        $job = new GenerateNewspaperJob;
        $job->handle(app(NewspaperService::class));
    });

    it('skips user when edition already exists for today', function () {
        $user = User::factory()->create();
        TrackedEntity::factory()->create(['is_active' => true]);
        NewspaperEdition::factory()->create([
            'user_id' => $user->id,
            'edition_date' => now()->toDateString(),
        ]);

        $mockService = mock(NewspaperService::class);
        $mockService->shouldNotReceive('generateForUser');

        app()->instance(NewspaperService::class, $mockService);

        $job = new GenerateNewspaperJob;
        $job->handle(app(NewspaperService::class));
    });
});

describe('Newspaper Livewire Component', function () {
    it('renders without edition', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Newspaper::class)
            ->assertStatus(200)
            ->assertSee('No edition available yet');
    });

    it('renders with edition', function () {
        $user = User::factory()->create();
        NewspaperEdition::factory()->create([
            'user_id' => $user->id,
            'headline' => 'Test Daily Headline',
        ]);

        Livewire::actingAs($user)
            ->test(Newspaper::class)
            ->assertStatus(200)
            ->assertSee('Test Daily Headline');
    });

    it('shows regenerate button', function () {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Newspaper::class)
            ->assertSee('Generate Today\'s Edition');
    });
});

describe('News Route', function () {
    it('requires authentication', function () {
        $this->get(route('news.index'))
            ->assertRedirect(route('login'));
    });

    it('renders newspaper page for authenticated user', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('news.index'))
            ->assertStatus(200);
    });
});
