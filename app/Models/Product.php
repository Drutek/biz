<?php

namespace App\Models;

use App\Enums\BillingFrequency;
use App\Enums\PricingModel;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'product_type',
        'status',
        'price',
        'pricing_model',
        'billing_frequency',
        'mrr',
        'total_revenue',
        'subscriber_count',
        'units_sold',
        'hours_invested',
        'monthly_maintenance_hours',
        'launched_at',
        'target_launch_date',
        'url',
        'notes',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'status' => ProductStatus::class,
            'pricing_model' => PricingModel::class,
            'billing_frequency' => BillingFrequency::class,
            'price' => 'decimal:2',
            'mrr' => 'decimal:2',
            'total_revenue' => 'decimal:2',
            'subscriber_count' => 'integer',
            'units_sold' => 'integer',
            'hours_invested' => 'decimal:2',
            'monthly_maintenance_hours' => 'decimal:2',
            'launched_at' => 'date',
            'target_launch_date' => 'date',
        ];
    }

    /**
     * @return HasMany<ProductMilestone, $this>
     */
    public function milestones(): HasMany
    {
        return $this->hasMany(ProductMilestone::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<ProductRevenueSnapshot, $this>
     */
    public function revenueSnapshots(): HasMany
    {
        return $this->hasMany(ProductRevenueSnapshot::class)->orderByDesc('recorded_at');
    }

    /**
     * @return MorphMany<BusinessEvent, $this>
     */
    public function businessEvents(): MorphMany
    {
        return $this->morphMany(BusinessEvent::class, 'eventable');
    }

    public function effectiveHourlyRate(): float
    {
        if ($this->hours_invested <= 0) {
            return 0;
        }

        return (float) $this->total_revenue / (float) $this->hours_invested;
    }

    public function revenueTrend(): ?float
    {
        $snapshots = $this->revenueSnapshots()
            ->where('recorded_at', '>=', now()->subMonths(3))
            ->orderBy('recorded_at')
            ->get();

        if ($snapshots->count() < 2) {
            return null;
        }

        $oldest = $snapshots->first();
        $newest = $snapshots->last();

        if ($this->pricing_model === PricingModel::Subscription) {
            $oldValue = (float) $oldest->mrr;
            $newValue = (float) $newest->mrr;
        } else {
            $oldValue = (float) $oldest->total_revenue;
            $newValue = (float) $newest->total_revenue;
        }

        if ($oldValue <= 0) {
            return $newValue > 0 ? 100 : 0;
        }

        return (($newValue - $oldValue) / $oldValue) * 100;
    }

    public function isLaunched(): bool
    {
        return $this->status === ProductStatus::Launched;
    }

    public function isInDevelopment(): bool
    {
        return $this->status->isInDevelopment();
    }

    public function daysToLaunch(): ?int
    {
        if ($this->isLaunched() || ! $this->target_launch_date) {
            return null;
        }

        return (int) now()->diffInDays($this->target_launch_date, false);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', ProductStatus::Retired);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeLaunched(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Launched);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeInDevelopment(Builder $query): Builder
    {
        return $query->whereIn('status', [
            ProductStatus::Idea,
            ProductStatus::Planning,
            ProductStatus::InDevelopment,
            ProductStatus::Testing,
        ]);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeByType(Builder $query, ProductType $type): Builder
    {
        return $query->where('product_type', $type);
    }
}
