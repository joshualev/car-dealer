<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Car extends Model
{
    /** @use HasFactory<\Database\Factories\CarFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'manufacturer_id',
        'model',
        'year',
        'colour',
    ];

    /**
     * Get the manufacturer that owns the car.
     */
    public function manufacturer(): BelongsTo {
        return $this->belongsTo(Manufacturer::class);
    }


    /**
     * Scope a query to search cars by model or manufacturer name.
     *
     * @param  Builder  $query
     * @param  string|null  $term
     * @return Builder
     */
    public function scopeSearch($query, ?string $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where('model', 'like', '%' . $term . '%')
                     ->orWhereHas('manufacturer', function ($q) use ($term) {
                         $q->where('name', 'like', '%' . $term . '%');
                     });
    }
}
