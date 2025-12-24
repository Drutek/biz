<?php

namespace App\Models;

use App\Enums\BillingFrequency;
use App\Enums\ContractStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    /** @use HasFactory<\Database\Factories\ContractFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'value',
        'billing_frequency',
        'start_date',
        'end_date',
        'probability',
        'status',
        'notes',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'billing_frequency' => BillingFrequency::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'probability' => 'integer',
            'status' => ContractStatus::class,
        ];
    }

    public function monthlyValue(): float
    {
        return (float) $this->value * $this->billing_frequency->monthlyMultiplier();
    }

    public function weightedValue(): float
    {
        return (float) $this->value * ($this->probability / 100);
    }

    public function weightedMonthlyValue(): float
    {
        return $this->monthlyValue() * ($this->probability / 100);
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [ContractStatus::Confirmed, ContractStatus::Pipeline])
            ->where('start_date', '<=', now())
            ->where(function (Builder $q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', ContractStatus::Confirmed);
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    public function scopePipeline(Builder $query): Builder
    {
        return $query->where('status', ContractStatus::Pipeline);
    }

    /**
     * @param  Builder<Contract>  $query
     * @return Builder<Contract>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', ContractStatus::Completed);
    }

    public function isActive(): bool
    {
        if (! in_array($this->status, [ContractStatus::Confirmed, ContractStatus::Pipeline])) {
            return false;
        }

        if ($this->start_date->isFuture()) {
            return false;
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }

        return true;
    }
}
