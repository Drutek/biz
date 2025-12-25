<?php

namespace App\Traits;

use App\Jobs\GenerateEmbeddingJob;
use Illuminate\Database\Eloquent\Builder;

trait HasEmbedding
{
    /**
     * Boot the trait and register model events.
     */
    public static function bootHasEmbedding(): void
    {
        static::created(function ($model) {
            if ($model->shouldAutoEmbed()) {
                GenerateEmbeddingJob::dispatch(
                    static::class,
                    $model->id
                )->delay(now()->addSeconds(5));
            }
        });

        static::updated(function ($model) {
            if ($model->shouldReEmbed()) {
                GenerateEmbeddingJob::dispatch(
                    static::class,
                    $model->id
                )->delay(now()->addSeconds(5));
            }
        });
    }

    /**
     * Scope a query to only include embedded records.
     */
    public function scopeEmbedded(Builder $query): Builder
    {
        return $query->where('is_embedded', true);
    }

    /**
     * Scope a query to only include non-embedded records.
     */
    public function scopeNotEmbedded(Builder $query): Builder
    {
        return $query->where('is_embedded', false);
    }

    /**
     * Check if this model should automatically generate an embedding on create.
     */
    protected function shouldAutoEmbed(): bool
    {
        return true;
    }

    /**
     * Check if this model should regenerate its embedding on update.
     */
    protected function shouldReEmbed(): bool
    {
        $embeddableColumns = $this->getEmbeddableColumns();

        foreach ($embeddableColumns as $column) {
            if ($this->wasChanged($column)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the columns that affect the embedding.
     *
     * @return array<string>
     */
    protected function getEmbeddableColumns(): array
    {
        return ['content', 'title', 'description', 'snippet'];
    }

    /**
     * Queue a job to regenerate this model's embedding.
     */
    public function regenerateEmbedding(): void
    {
        GenerateEmbeddingJob::dispatch(
            static::class,
            $this->id
        );
    }

    /**
     * Check if this model has been embedded.
     */
    public function isEmbedded(): bool
    {
        return (bool) $this->is_embedded;
    }
}
