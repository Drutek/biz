<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRevenueSnapshot extends Model
{
    /** @use HasFactory<\Database\Factories\ProductRevenueSnapshotFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'mrr',
        'total_revenue',
        'subscriber_count',
        'units_sold',
        'recorded_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'mrr' => 'decimal:2',
            'total_revenue' => 'decimal:2',
            'subscriber_count' => 'integer',
            'units_sold' => 'integer',
            'recorded_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @param  Builder<ProductRevenueSnapshot>  $query
     * @return Builder<ProductRevenueSnapshot>
     */
    public function scopeForPeriod(Builder $query, \Carbon\Carbon $start, \Carbon\Carbon $end): Builder
    {
        return $query->whereBetween('recorded_at', [$start, $end]);
    }

    public static function captureSnapshot(Product $product): self
    {
        return self::updateOrCreate(
            [
                'product_id' => $product->id,
                'recorded_at' => now()->startOfMonth()->toDateString(),
            ],
            [
                'mrr' => $product->mrr,
                'total_revenue' => $product->total_revenue,
                'subscriber_count' => $product->subscriber_count,
                'units_sold' => $product->units_sold,
            ]
        );
    }
}
