<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiProductDescriptionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'product_name',
        'category',
        'pet_type',
        'input_payload',
        'generated_payload',
        'provider_name',
        'model_name',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'status',
        'error_message',
    ];

    protected $casts = [
        'input_payload' => 'array',
        'generated_payload' => 'array',
    ];
}
