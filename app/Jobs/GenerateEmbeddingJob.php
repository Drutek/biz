<?php

namespace App\Jobs;

use App\Models\AdvisoryMessage;
use App\Models\BusinessEvent;
use App\Models\NewsItem;
use App\Models\ProactiveInsight;
use App\Services\Embedding\EmbeddingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateEmbeddingJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $modelClass,
        public int $modelId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(EmbeddingService $embeddingService): void
    {
        /** @var Model|null $model */
        $model = $this->modelClass::find($this->modelId);

        if (! $model) {
            Log::warning('Model not found for embedding generation', [
                'class' => $this->modelClass,
                'id' => $this->modelId,
            ]);

            return;
        }

        $text = $this->getTextToEmbed($model);

        if (empty(trim($text))) {
            Log::debug('Empty text for embedding, skipping', [
                'class' => $this->modelClass,
                'id' => $this->modelId,
            ]);

            return;
        }

        $embedding = $embeddingService->embed($text);

        if (! $embedding) {
            Log::error('Failed to generate embedding', [
                'class' => $this->modelClass,
                'id' => $this->modelId,
            ]);

            return;
        }

        $vectorString = $embeddingService->toVectorString($embedding);

        DB::table($model->getTable())
            ->where('id', $model->getKey())
            ->update([
                'embedding' => $vectorString,
                'is_embedded' => true,
            ]);

        Log::debug('Successfully generated embedding', [
            'class' => $this->modelClass,
            'id' => $this->modelId,
            'text_length' => strlen($text),
        ]);
    }

    /**
     * Get the text content to embed based on model type.
     */
    protected function getTextToEmbed(Model $model): string
    {
        return match ($this->modelClass) {
            AdvisoryMessage::class => $model->content ?? '',

            NewsItem::class => implode("\n\n", array_filter([
                $model->title ?? '',
                $model->snippet ?? '',
            ])),

            BusinessEvent::class => implode("\n\n", array_filter([
                $model->title ?? '',
                $model->description ?? '',
            ])),

            ProactiveInsight::class => implode("\n\n", array_filter([
                $model->title ?? '',
                $model->content ?? '',
            ])),

            default => $model->content ?? $model->description ?? $model->title ?? '',
        };
    }
}
