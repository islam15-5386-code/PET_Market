<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('order_number', 60)->unique();
            $table->enum('status', [
                'pending', 'confirmed', 'processing',
                'shipped', 'delivered', 'cancelled',
            ])->default('pending');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_fee', 8, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('shipping_name');
            $table->string('shipping_phone', 20);
            $table->text('shipping_address');
            $table->string('shipping_city', 100);
            $table->string('shipping_postal_code', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
