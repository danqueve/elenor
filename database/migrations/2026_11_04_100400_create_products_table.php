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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 40)->unique();
            $table->string('manufacturer_code', 60)->nullable()->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            // u = unit, m = meter, rollo = roll. Prices are per unit of measure.
            $table->string('unit', 10)->default('u');
            $table->decimal('list_price', 12, 2);
            // Null when there is no professional discount: they pay the list price.
            $table->decimal('professional_price', 12, 2)->nullable();
            // Decimal so cable can be sold by the meter.
            $table->decimal('stock', 10, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // Safety net for the rule "never sell the last unit twice": even a bug
        // in the application can't leave negative stock or negative prices.
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_stock_non_negative CHECK (stock >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_list_price_non_negative CHECK (list_price >= 0)');
        DB::statement('ALTER TABLE products ADD CONSTRAINT products_professional_price_non_negative CHECK (professional_price IS NULL OR professional_price >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
