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

    public const KEY_NEWS_RECENCY = 'news_recency';

    public const DEFAULT_NEWS_RECENCY = 'w';

    /**
     * Supported news recency options.
     *
     * @return array<string, array{label: string, description: string}>
     */
    public static function newsRecencyOptions(): array
    {
        return [
            'h' => ['label' => 'Last hour', 'description' => 'Only show news from the past hour'],
            'd' => ['label' => 'Last 24 hours', 'description' => 'Only show news from today'],
            'w' => ['label' => 'Last week', 'description' => 'Only show news from the past 7 days'],
            'm' => ['label' => 'Last month', 'description' => 'Only show news from the past 30 days'],
            '' => ['label' => 'Any time', 'description' => 'Show all available news'],
        ];
    }

    public const KEY_COMPANY_NAME = 'company_name';

    public const KEY_FINANCIAL_YEAR_START = 'financial_year_start';

    public const KEY_BUSINESS_DESCRIPTION = 'business_description';

    public const KEY_BUSINESS_INDUSTRY = 'business_industry';

    public const KEY_BUSINESS_TARGET_MARKET = 'business_target_market';

    public const KEY_BUSINESS_KEY_SERVICES = 'business_key_services';

    public const KEY_CASH_BALANCE = 'cash_balance';

    public const KEY_HOURLY_RATE = 'hourly_rate';

    public const KEY_CURRENCY = 'currency';

    public const DEFAULT_CURRENCY = 'USD';

    /**
     * Supported currencies with their symbols and formatting.
     *
     * @return array<string, array{symbol: string, name: string, position: string}>
     */
    public static function supportedCurrencies(): array
    {
        return [
            'USD' => ['symbol' => '$', 'name' => 'US Dollar', 'position' => 'before'],
            'EUR' => ['symbol' => '€', 'name' => 'Euro', 'position' => 'before'],
            'GBP' => ['symbol' => '£', 'name' => 'British Pound', 'position' => 'before'],
            'CAD' => ['symbol' => 'C$', 'name' => 'Canadian Dollar', 'position' => 'before'],
            'AUD' => ['symbol' => 'A$', 'name' => 'Australian Dollar', 'position' => 'before'],
            'JPY' => ['symbol' => '¥', 'name' => 'Japanese Yen', 'position' => 'before'],
            'CHF' => ['symbol' => 'CHF', 'name' => 'Swiss Franc', 'position' => 'before'],
            'INR' => ['symbol' => '₹', 'name' => 'Indian Rupee', 'position' => 'before'],
            'CNY' => ['symbol' => '¥', 'name' => 'Chinese Yuan', 'position' => 'before'],
            'BRL' => ['symbol' => 'R$', 'name' => 'Brazilian Real', 'position' => 'before'],
            'MXN' => ['symbol' => 'MX$', 'name' => 'Mexican Peso', 'position' => 'before'],
            'SEK' => ['symbol' => 'kr', 'name' => 'Swedish Krona', 'position' => 'after'],
            'NOK' => ['symbol' => 'kr', 'name' => 'Norwegian Krone', 'position' => 'after'],
            'DKK' => ['symbol' => 'kr', 'name' => 'Danish Krone', 'position' => 'after'],
            'NZD' => ['symbol' => 'NZ$', 'name' => 'New Zealand Dollar', 'position' => 'before'],
            'SGD' => ['symbol' => 'S$', 'name' => 'Singapore Dollar', 'position' => 'before'],
            'HKD' => ['symbol' => 'HK$', 'name' => 'Hong Kong Dollar', 'position' => 'before'],
            'ZAR' => ['symbol' => 'R', 'name' => 'South African Rand', 'position' => 'before'],
            'PLN' => ['symbol' => 'zł', 'name' => 'Polish Zloty', 'position' => 'after'],
            'KRW' => ['symbol' => '₩', 'name' => 'South Korean Won', 'position' => 'before'],
        ];
    }

    /**
     * Get the current currency code.
     */
    public static function currency(): string
    {
        return self::get(self::KEY_CURRENCY, self::DEFAULT_CURRENCY);
    }

    /**
     * Get the currency symbol for the current or specified currency.
     */
    public static function currencySymbol(?string $currency = null): string
    {
        $currency = $currency ?? self::currency();
        $currencies = self::supportedCurrencies();

        return $currencies[$currency]['symbol'] ?? '$';
    }

    /**
     * Format a value as currency.
     */
    public static function formatCurrency(float $value, ?string $currency = null, int $decimals = 2): string
    {
        $currency = $currency ?? self::currency();
        $currencies = self::supportedCurrencies();
        $config = $currencies[$currency] ?? $currencies[self::DEFAULT_CURRENCY];

        $formatted = number_format(abs($value), $decimals);
        $sign = $value < 0 ? '-' : '';

        if ($config['position'] === 'after') {
            return $sign.$formatted.' '.$config['symbol'];
        }

        return $sign.$config['symbol'].$formatted;
    }

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
