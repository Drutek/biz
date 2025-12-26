<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskSuggestion extends Model
{
    /** @use HasFactory<\Database\Factories\TaskSuggestionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'proactive_insight_id',
        'suggestion_hash',
        'was_accepted',
        'was_rejected',
        'suggested_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'was_accepted' => 'boolean',
            'was_rejected' => 'boolean',
            'suggested_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ProactiveInsight, $this>
     */
    public function proactiveInsight(): BelongsTo
    {
        return $this->belongsTo(ProactiveInsight::class);
    }

    /**
     * Generate a hash for deduplication.
     */
    public static function generateHash(string $title, ?string $description = null): string
    {
        $content = strtolower(trim($title));
        if ($description) {
            $content .= '|'.strtolower(trim($description));
        }

        return hash('xxh3', $content);
    }

    /**
     * Check if a suggestion with this hash already exists for the user.
     */
    public static function existsForUser(int $userId, string $hash): bool
    {
        return static::query()
            ->where('user_id', $userId)
            ->where('suggestion_hash', $hash)
            ->exists();
    }

    public function markAccepted(): void
    {
        $this->update(['was_accepted' => true]);
    }

    public function markRejected(): void
    {
        $this->update(['was_rejected' => true]);
    }
}
