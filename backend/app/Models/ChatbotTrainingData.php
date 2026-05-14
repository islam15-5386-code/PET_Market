<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotTrainingData extends Model
{
    use HasFactory;

    protected $fillable = [
        'question', 'answer', 'intent', 'pet_type', 'category', 'age_group',
        'language', 'source', 'is_approved',
    ];
}
