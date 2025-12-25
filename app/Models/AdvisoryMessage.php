<?php

namespace App\Models;

use App\Enums\LLMProvider;
use App\Enums\MessageRole;
use App\Traits\HasEmbedding;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisoryMessage extends Model
{
    /** @use HasFactory<\Database\Factories\AdvisoryMessageFactory> */
    use HasEmbedding;

    use HasFactory;

    protected $fillable = [
        'advisory_thread_id',
        'role',
        'content',
        'provider',
        'model',
        'tokens_used',
        'is_embedded',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'role' => MessageRole::class,
            'provider' => LLMProvider::class,
            'tokens_used' => 'integer',
            'is_embedded' => 'boolean',
        ];
    }

    /**
     * @return array<string>
     */
    protected function getEmbeddableColumns(): array
    {
        return ['content'];
    }

    /**
     * @return BelongsTo<AdvisoryThread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(AdvisoryThread::class, 'advisory_thread_id');
    }

    /**
     * Alias for factory relationship.
     *
     * @return BelongsTo<AdvisoryThread, $this>
     */
    public function advisoryThread(): BelongsTo
    {
        return $this->thread();
    }

    public function isFromUser(): bool
    {
        return $this->role === MessageRole::User;
    }

    public function isFromAssistant(): bool
    {
        return $this->role === MessageRole::Assistant;
    }
}
