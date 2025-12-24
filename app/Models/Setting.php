<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    /** @use HasFactory<\Database\Factories\SettingFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    public const KEY_PREFERRED_LLM_PROVIDER = 'preferred_llm_provider';

    public const KEY_OPENAI_API_KEY = 'openai_api_key';

    public const KEY_ANTHROPIC_API_KEY = 'anthropic_api_key';

    public const KEY_SERPAPI_KEY = 'serpapi_key';

    public const KEY_COMPANY_NAME = 'company_name';

    public const KEY_FINANCIAL_YEAR_START = 'financial_year_start';

    public const KEY_BUSINESS_DESCRIPTION = 'business_description';

    public const KEY_BUSINESS_INDUSTRY = 'business_industry';

    public const KEY_BUSINESS_TARGET_MARKET = 'business_target_market';

    public const KEY_BUSINESS_KEY_SERVICES = 'business_key_services';

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = self::query()->where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        self::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("setting.{$key}");
    }

    public static function remove(string $key): void
    {
        self::query()->where('key', $key)->delete();
        Cache::forget("setting.{$key}");
    }

    /**
     * @return array<string, mixed>
     */
    public static function allSettings(): array
    {
        return self::query()
            ->pluck('value', 'key')
            ->toArray();
    }
}
