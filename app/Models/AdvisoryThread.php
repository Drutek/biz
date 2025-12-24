<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdvisoryThread extends Model
{
    /** @use HasFactory<\Database\Factories\AdvisoryThreadFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'context_snapshot',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'context_snapshot' => 'array',
        ];
    }

    /**
     * @return HasMany<AdvisoryMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AdvisoryMessage::class)->orderBy('created_at');
    }

    public function latestMessage(): ?AdvisoryMessage
    {
        return $this->messages()->latest()->first();
    }

    public function addUserMessage(string $content): AdvisoryMessage
    {
        return $this->messages()->create([
            'role' => 'user',
            'content' => $content,
        ]);
    }

    public function addAssistantMessage(string $content, string $provider, string $model, ?int $tokensUsed = null): AdvisoryMessage
    {
        return $this->messages()->create([
            'role' => 'assistant',
            'content' => $content,
            'provider' => $provider,
            'model' => $model,
            'tokens_used' => $tokensUsed,
        ]);
    }

    /**
     * @return array<array{role: string, content: string}>
     */
    public function getMessagesForLLM(): array
    {
        return $this->messages
            ->map(fn (AdvisoryMessage $message) => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->toArray();
    }
}
