<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'session_uuid', 'status', 'started_at', 'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(ChatbotMessage::class);
    }
}
