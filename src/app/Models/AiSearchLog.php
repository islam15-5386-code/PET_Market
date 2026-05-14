<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSearchLog extends Model
{
    protected $fillable = [
        'user_id',
        'query',
        'detected_pet_type',
        'detected_category',
        'detected_age_group',
        'detected_brand',
        'detected_price_min',
        'detected_price_max',
        'confidence',
        'total_results',
        'filters_payload',
    ];

    protected $casts = [
        'detected_price_min' => 'decimal:2',
        'detected_price_max' => 'decimal:2',
        'confidence' => 'decimal:4',
        'filters_payload' => 'array',
    ];
}

