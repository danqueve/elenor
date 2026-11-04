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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // Null if the product is deleted later: the order keeps its frozen data.
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            // Frozen at purchase time: later changes to the product (name,
            // price, unit) never alter an existing order.
            $table->string('product_sku', 40);
            $table->string('product_name');
            $table->string('product_unit', 10);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('quantity', 10, 2);
            $table->decimal('subtotal', 12, 2);

            $table->timestamps();
        });

        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_unit_price_non_negative CHECK (unit_price >= 0)');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_quantity_positive CHECK (quantity > 0)');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_subtotal_non_negative CHECK (subtotal >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
