<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'slug', 'description',
        'price', 'stock_quantity', 'images', 'location', 'is_available',
        'brand', 'sku', 'rating', 'review_count',
    ];

    protected $casts = [
        'images'       => 'array',
        'price'        => 'decimal:2',
        'is_available' => 'boolean',
        'rating'       => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function aiDescriptions()
    {
        return $this->hasMany(AiProductDescription::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────────
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)->where('stock_quantity', '>', 0);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $q->where('name', $op, "%{$term}%")
              ->orWhere('description', $op, "%{$term}%");
        });
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByPriceRange($query, ?float $min, ?float $max)
    {
        if ($min !== null) $query->where('price', '>=', $min);
        if ($max !== null) $query->where('price', '<=', $max);
        return $query;
    }

    public function scopeByLocation($query, string $location)
    {
        $op = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
        return $query->where('location', $op, "%{$location}%");
    }

    // ── Helpers ────────────────────────────────────────────────────────────────
    public function decrementStock(int $quantity): void
    {
        $this->decrement('stock_quantity', $quantity);
        if ($this->stock_quantity <= 0) {
            $this->update(['is_available' => false]);
        }
    }
}
