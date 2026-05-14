<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'chatbot_message_id', 'product_id', 'score', 'reason',
    ];

    public function message()
    {
        return $this->belongsTo(ChatbotMessage::class, 'chatbot_message_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
