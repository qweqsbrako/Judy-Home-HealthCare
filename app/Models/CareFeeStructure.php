<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CareFeeStructure extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'care_fee_structure';

    protected $fillable = [
        'fee_type',
        'care_type',
        'name',
        'description',
        'base_amount',
        'currency',
        'tax_percentage',
        'min_hours',
        'max_hours',
        'duration_days',
        'is_active',
        'valid_from',
        'valid_until',
        'region_overrides',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'region_overrides' => 'array',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    protected $appends = [
        'formatted_amount',
        'total_with_tax',
    ];

    /**
     * Accessors
     */
    public function getFormattedAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->base_amount, 2);
    }

    public function getTotalWithTaxAttribute(): float
    {
        $taxAmount = $this->base_amount * ($this->tax_percentage / 100);
        return $this->base_amount + $taxAmount;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')
                  ->orWhere('valid_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', now());
            });
    }

    public function scopeByFeeType($query, $type)
    {
        return $query->where('fee_type', $type);
    }

    public function scopeByCareType($query, $type)
    {
        return $query->where(function ($q) use ($type) {
            $q->where('care_type', $type)
              ->orWhere('care_type', 'all');
        });
    }

    /**
     * Get amount for specific region
     */
    public function getAmountForRegion(?string $region): float
    {
        if ($region && $this->region_overrides && isset($this->region_overrides[$region])) {
            return (float) $this->region_overrides[$region];
        }

        return $this->base_amount;
    }

    /**
     * Calculate total with tax for region
     */
    public function getTotalForRegion(?string $region): float
    {
        $amount = $this->getAmountForRegion($region);
        $taxAmount = $amount * ($this->tax_percentage / 100);
        return $amount + $taxAmount;
    }

    /**
     * Get active assessment fee
     */
    public static function getAssessmentFee(?string $careType = null, ?string $region = null): ?self
    {
        $query = static::active()
            ->byFeeType('assessment_fee');

        if ($careType) {
            $query->byCareType($careType);
        }

        $fee = $query->first();

        return $fee;
    }

    /**
     * Get care rates
     */
    public static function getCareRates(string $careType, ?string $region = null): array
    {
        return static::active()
            ->where('care_type', $careType)
            ->whereIn('fee_type', ['hourly_rate', 'daily_rate', 'package'])
            ->get()
            ->map(function ($fee) use ($region) {
                return [
                    'id' => $fee->id,
                    'type' => $fee->fee_type,
                    'name' => $fee->name,
                    'description' => $fee->description,
                    'amount' => $fee->getAmountForRegion($region),
                    'total' => $fee->getTotalForRegion($region),
                    'currency' => $fee->currency,
                    'duration_days' => $fee->duration_days,
                    'min_hours' => $fee->min_hours,
                    'max_hours' => $fee->max_hours,
                ];
            })
            ->toArray();
    }
}