<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiProductDescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'title',
        'description',
        'seo_keywords',
        'benefits',
        'source',
        'prompt_hash',
    ];

    protected $casts = [
        'seo_keywords' => 'array',
        'benefits' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

