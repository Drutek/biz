<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewspaperEdition extends Model
{
    /** @use HasFactory<\Database\Factories\NewspaperEditionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'edition_date',
        'headline',
        'summary',
        'sections',
        'generated_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'edition_date' => 'date',
            'sections' => 'array',
            'generated_at' => 'datetime',
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
     * @param  Builder<NewspaperEdition>  $query
     * @return Builder<NewspaperEdition>
     */
    public function scopeForDate(Builder $query, \Carbon\Carbon $date): Builder
    {
        return $query->whereDate('edition_date', $date);
    }

    /**
     * @param  Builder<NewspaperEdition>  $query
     * @return Builder<NewspaperEdition>
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->forDate(now());
    }

    public static function todayForUser(User $user): ?self
    {
        return static::query()
            ->where('user_id', $user->id)
            ->today()
            ->first();
    }
}
