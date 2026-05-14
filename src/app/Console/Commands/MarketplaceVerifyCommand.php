<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MarketplaceVerifyCommand extends Command
{
    protected $signature = 'marketplace:verify';
    protected $description = 'Verify core marketplace data integrity and baseline functionality';

    public function handle(): int
    {
        $this->info('Marketplace verification report');

        $users = User::query()->count();
        $admins = User::query()->where('role', 'admin')->count();
        $categories = Category::query()->count();
        $products = Product::query()->count();
        $availableProducts = Product::query()->where('is_available', true)->count();
        $orders = Order::query()->count();

        $productsWithImage = Product::query()
            ->where(function ($q) {
                $q->whereNotNull('image_url')->orWhereNotNull('images');
            })
            ->count();

        $productsWithoutImage = max(0, $products - $productsWithImage);

        $this->line("Users: {$users}");
        $this->line("Admin users: {$admins}");
        $this->line("Categories: {$categories}");
        $this->line("Products: {$products}");
        $this->line("Available products: {$availableProducts}");
        $this->line("Orders: {$orders}");
        $this->line("Products with image fields: {$productsWithImage}");
        $this->line("Products missing image fields: {$productsWithoutImage}");

        if (DB::getSchemaBuilder()->hasTable('chatbot_sessions')) {
            $chatSessions = DB::table('chatbot_sessions')->count();
            $chatMessages = DB::table('chatbot_messages')->count();
            $this->line("Chat sessions: {$chatSessions}");
            $this->line("Chat messages: {$chatMessages}");
        }

        if (DB::getSchemaBuilder()->hasTable('ai_product_description_logs')) {
            $descLogs = DB::table('ai_product_description_logs')->count();
            $this->line("AI description logs: {$descLogs}");
        }

        if (DB::getSchemaBuilder()->hasTable('ai_search_logs')) {
            $searchLogs = DB::table('ai_search_logs')->count();
            $this->line("AI search logs: {$searchLogs}");
        }

        return self::SUCCESS;
    }
}

