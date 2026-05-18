<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        if (!Product::query()->exists()) {
            $this->command->warn('No products found; skipping OrderSeeder.');
            return;
        }

        $users = User::query()
            ->where('role', 'user')
            ->get();

        if ($users->isEmpty()) {
            $users = collect(range(1, 8))->map(function (int $i) {
                return User::create([
                    'name' => "Demo User {$i}",
                    'email' => "demo-user-{$i}@petmarketplace.com",
                    'password' => Hash::make('Password@123'),
                    'role' => 'user',
                    'is_active' => true,
                    'phone' => '01700000000',
                    'address' => 'Demo Address',
                    'city' => 'Dhaka',
                    'postal_code' => '1207',
                ]);
            });
        }

        $statuses = [
            Order::STATUS_DELIVERED,
            Order::STATUS_DELIVERED,
            Order::STATUS_DELIVERED,
            Order::STATUS_SHIPPED,
            Order::STATUS_PROCESSING,
            Order::STATUS_PENDING,
            Order::STATUS_CANCELLED,
        ];

        $ordersToCreate = 45;

        for ($i = 0; $i < $ordersToCreate; $i++) {
            $user = $users->random();
            $status = $statuses[array_rand($statuses)];
            $createdAt = now()->subDays(rand(0, 29))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            $itemsCount = rand(1, 3);

            $pickedProducts = $this->pickAvailableProducts($itemsCount);

            if ($pickedProducts->isEmpty()) {
                $this->command->warn('No available products found; skipping one demo order.');
                continue;
            }

            $subtotal = 0.0;

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'status' => $status,
                'subtotal' => 0,
                'shipping_fee' => 60.00,
                'total_amount' => 0,
                'shipping_name' => $user->name,
                'shipping_phone' => $user->phone ?? '01700000000',
                'shipping_address' => $user->address ?? 'Demo Address',
                'shipping_city' => $user->city ?? 'Dhaka',
                'shipping_postal_code' => $user->postal_code ?? '1207',
                'notes' => null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            foreach ($pickedProducts as $product) {
                $qty = rand(1, 3);
                $unit = (float) $product->price;
                $line = $unit * $qty;
                $subtotal += $line;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_image' => null,
                    'quantity' => $qty,
                    'unit_price' => number_format($unit, 2, '.', ''),
                    'total_price' => number_format($line, 2, '.', ''),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }

            $totalAmount = $subtotal + 60.00;
            $order->update([
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'total_amount' => number_format($totalAmount, 2, '.', ''),
            ]);
        }

        $this->command->info("Seeded {$ordersToCreate} demo orders for dashboard charts.");
    }

    private function pickAvailableProducts(int $count)
    {
        $minId = (int) Product::query()->min('id');
        $maxId = (int) Product::query()->max('id');
        $products = collect();
        $seen = [];
        $attempts = 0;

        while ($products->count() < $count && $attempts < 40) {
            $attempts++;
            $randomId = random_int($minId, $maxId);
            $product = Product::query()
                ->available()
                ->where('id', '>=', $randomId)
                ->orderBy('id')
                ->first();

            if (!$product) {
                $product = Product::query()
                    ->available()
                    ->where('id', '<', $randomId)
                    ->orderByDesc('id')
                    ->first();
            }

            if ($product && !isset($seen[$product->id])) {
                $seen[$product->id] = true;
                $products->push($product);
            }
        }

        return $products;
    }
}
