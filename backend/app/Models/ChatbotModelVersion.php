<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotModelVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_name', 'model_path', 'vectorizer_path', 'training_rows_count',
        'accuracy', 'status', 'trained_at',
    ];

    protected $casts = [
        'trained_at' => 'datetime',
    ];
}
