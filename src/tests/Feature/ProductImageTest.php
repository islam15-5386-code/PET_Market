<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\ProductImageService;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    public function test_products_have_image_url_or_images(): void
    {
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

