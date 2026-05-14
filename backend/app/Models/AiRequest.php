<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'feature', 'input_hash', 'strategy_used',
        'prompt_tokens', 'completion_tokens', 'total_tokens',
        'status', 'error_message',
    ];
}
