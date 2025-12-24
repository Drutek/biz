<?php

use App\Jobs\FetchNewsJob;
use App\Models\TrackedEntity;
use App\Services\News\SerpApiService;
use Illuminate\Support\Facades\Queue;

describe('FetchNewsJob', function () {
    it('fetches news for all active entities', function () {
        TrackedEntity::factory()->create(['is_active' => true]);
        TrackedEntity::factory()->create(['is_active' => true]);
        TrackedEntity::factory()->create(['is_active' => false]);

        $mockService = mock(SerpApiService::class);
        $mockService->shouldReceive('fetchAll')
            ->once();

        app()->instance(SerpApiService::class, $mockService);

        $job = new FetchNewsJob;
        $job->handle(app(SerpApiService::class));
    });

    it('can be dispatched to queue', function () {
        Queue::fake();

        FetchNewsJob::dispatch();

        Queue::assertPushed(FetchNewsJob::class);
    });

    it('handles empty entity list gracefully', function () {
        $mockService = mock(SerpApiService::class);
        $mockService->shouldReceive('fetchAll')
            ->once();

        app()->instance(SerpApiService::class, $mockService);

        $job = new FetchNewsJob;
        $job->handle(app(SerpApiService::class));
    });
});
