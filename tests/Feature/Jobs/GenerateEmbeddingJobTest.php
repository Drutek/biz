<?php

use App\Jobs\GenerateEmbeddingJob;
use App\Models\AdvisoryMessage;
use App\Models\AdvisoryThread;
use App\Models\User;
use App\Services\Embedding\EmbeddingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('dispatches job when advisory message is created', function () {
    Queue::fake();

    $user = User::factory()->create();
    $thread = AdvisoryThread::factory()->for($user)->create();
    $message = AdvisoryMessage::factory()->inThread($thread)->create();

    Queue::assertPushed(GenerateEmbeddingJob::class, function ($job) use ($message) {
        return $job->modelClass === AdvisoryMessage::class
            && $job->modelId === $message->id;
    });
});

it('updates is_embedded flag after successful embedding', function () {
    if (DB::connection()->getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL with pgvector');
    }

    $user = User::factory()->create();
    $thread = AdvisoryThread::factory()->for($user)->create();
    $message = AdvisoryMessage::factory()->inThread($thread)->create([
        'content' => 'Test content for embedding',
        'is_embedded' => false,
    ]);

    $mockService = mock(EmbeddingService::class);
    $mockService->shouldReceive('embed')
        ->once()
        ->andReturn(array_fill(0, 1536, 0.1));
    $mockService->shouldReceive('toVectorString')
        ->once()
        ->andReturn('['.implode(',', array_fill(0, 1536, 0.1)).']');

    app()->instance(EmbeddingService::class, $mockService);

    $job = new GenerateEmbeddingJob(AdvisoryMessage::class, $message->id);
    $job->handle($mockService);

    $message->refresh();
    expect($message->is_embedded)->toBeTrue();
});

it('handles missing model gracefully', function () {
    $mockService = mock(EmbeddingService::class);
    $mockService->shouldNotReceive('embed');

    $job = new GenerateEmbeddingJob(AdvisoryMessage::class, 99999);
    $job->handle($mockService);
});

it('handles empty content gracefully', function () {
    if (DB::connection()->getDriverName() !== 'pgsql') {
        $this->markTestSkipped('This test requires PostgreSQL with pgvector');
    }

    $user = User::factory()->create();
    $thread = AdvisoryThread::factory()->for($user)->create();
    $message = AdvisoryMessage::factory()->inThread($thread)->create([
        'content' => '',
        'is_embedded' => false,
    ]);

    $mockService = mock(EmbeddingService::class);
    $mockService->shouldNotReceive('embed');

    $job = new GenerateEmbeddingJob(AdvisoryMessage::class, $message->id);
    $job->handle($mockService);

    $message->refresh();
    expect($message->is_embedded)->toBeFalse();
});

it('extracts correct text for advisory message', function () {
    $job = new GenerateEmbeddingJob(AdvisoryMessage::class, 1);

    $reflection = new ReflectionMethod($job, 'getTextToEmbed');
    $reflection->setAccessible(true);

    $model = new class extends \Illuminate\Database\Eloquent\Model
    {
        public string $content = 'Test message content';
    };

    $result = $reflection->invoke($job, $model);

    expect($result)->toBe('Test message content');
});
