<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * @return HasOne<UserPreference, $this>
     */
    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    /**
     * @return HasMany<BusinessEvent, $this>
     */
    public function businessEvents(): HasMany
    {
        return $this->hasMany(BusinessEvent::class);
    }

    /**
     * @return HasMany<DailyStandup, $this>
     */
    public function dailyStandups(): HasMany
    {
        return $this->hasMany(DailyStandup::class);
    }

    /**
     * Alias for dailyStandups().
     *
     * @return HasMany<DailyStandup, $this>
     */
    public function standups(): HasMany
    {
        return $this->dailyStandups();
    }

    /**
     * @return HasMany<ProactiveInsight, $this>
     */
    public function proactiveInsights(): HasMany
    {
        return $this->hasMany(ProactiveInsight::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<StandupEntry, $this>
     */
    public function standupEntries(): HasMany
    {
        return $this->hasMany(StandupEntry::class);
    }

    public function getOrCreatePreferences(): UserPreference
    {
        return $this->preferences ?? $this->preferences()->firstOrCreate([]);
    }

    public function unreadInsightsCount(): int
    {
        return $this->proactiveInsights()->unread()->active()->count();
    }

    public function todaysStandup(): ?DailyStandup
    {
        return $this->dailyStandups()->forDate(now())->first();
    }
}
