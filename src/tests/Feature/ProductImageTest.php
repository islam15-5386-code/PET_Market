<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_have_image_url_or_images(): void
    {
        $category = Category::query()->create(['name' => 'Cat Food', 'slug' => 'cat-food']);

        Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Seeded Cat Food',
            'slug' => 'seeded-cat-food',
            'price' => 500,
            'stock_quantity' => 10,
            'is_available' => true,
            'image_url' => '/products/cat/food/dry-food/cat-dry-food-1.jpg',
        ]);

        $products = Product::query()->limit(200)->get();
        $this->assertNotEmpty($products);

        foreach ($products as $product) {
            $hasImage = !empty($product->image_url) || !empty($product->images);
            $this->assertTrue($hasImage, "Product {$product->id} is missing image fields.");
        }
    }

    public function test_image_mapping_service_never_returns_null(): void
    {
        $service = app(ProductImageService::class);
        $image = $service->getImage('cat', 'food', 'dry_food');
        $this->assertNotEmpty($image);
    }
}
