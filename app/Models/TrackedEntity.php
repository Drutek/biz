<?php

namespace App\Models;

use App\Enums\EntityType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrackedEntity extends Model
{
    /** @use HasFactory<\Database\Factories\TrackedEntityFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'entity_type',
        'search_query',
        'negative_terms',
        'is_active',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'entity_type' => EntityType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<NewsItem, $this>
     */
    public function newsItems(): HasMany
    {
        return $this->hasMany(NewsItem::class);
    }

    /**
     * @param  Builder<TrackedEntity>  $query
     * @return Builder<TrackedEntity>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<TrackedEntity>  $query
     * @return Builder<TrackedEntity>
     */
    public function scopeOfType(Builder $query, EntityType $type): Builder
    {
        return $query->where('entity_type', $type);
    }

    /**
     * Build the effective search query including negative terms.
     */
    public function getEffectiveSearchQuery(): string
    {
        $query = $this->search_query;

        if (empty($this->negative_terms)) {
            return $query;
        }

        $terms = array_map('trim', explode(',', $this->negative_terms));
        $terms = array_filter($terms, fn ($term) => $term !== '');

        foreach ($terms as $term) {
            $query .= ' -"'.$term.'"';
        }

        return $query;
    }
}
