<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiSearchLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'query',
        'detected_pet_type',
        'detected_category',
        'detected_age_group',
        'detected_brand',
        'detected_price_min',
        'detected_price_max',
        'strategy_used',
        'confidence',
        'total_results',
    ];
}
