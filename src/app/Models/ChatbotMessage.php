<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chatbot_session_id',
        'sender',
        'message',
        'intent',
        'pet_type',
        'category',
        'age_group',
        'safety_level',
        'ai_payload',
    ];

    protected $casts = [
        'ai_payload' => 'array',
    ];

    public function session()
    {
        return $this->belongsTo(ChatbotSession::class, 'chatbot_session_id');
    }

    public function recommendations()
    {
        return $this->hasMany(ChatbotRecommendation::class);
    }
}
