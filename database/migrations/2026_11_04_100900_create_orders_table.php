<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            // Orders are kept: a user with orders can't be deleted.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('received');
            $table->string('shipping_method', 20);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->string('payment_method', 30);
            $table->string('payment_status', 20)->default('pending');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);
            $table->text('notes')->nullable();

            // Address snapshot: copied from the customer's address at purchase
            // time so later edits never change an old order. Empty for pickup.
            $table->string('shipping_street', 150)->nullable();
            $table->string('shipping_number', 20)->nullable();
            $table->string('shipping_city', 120)->nullable();
            $table->string('shipping_province', 100)->nullable();
            $table->string('shipping_postal_code', 10)->nullable();
            $table->string('shipping_reference')->nullable();

            $table->timestamps();

            // Admin dashboard: today's orders by status.
            $table->index(['status', 'created_at']);
        });

        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_shipping_cost_non_negative CHECK (shipping_cost >= 0)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_subtotal_non_negative CHECK (subtotal >= 0)');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_total_non_negative CHECK (total >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
