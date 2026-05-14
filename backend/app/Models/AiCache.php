<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiCache extends Model
{
    use HasFactory;

    protected $table = 'ai_cache';

    protected $fillable = [
        'feature', 'cache_key', 'input_payload', 'output_payload', 'expires_at',
    ];

    protected $casts = [
        'input_payload' => 'array',
        'output_payload' => 'array',
        'expires_at' => 'datetime',
    ];
}
