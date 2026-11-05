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
        Schema::table('products', function (Blueprint $table) {
            // The price before the offer. When it is higher than list_price the
            // product is "on sale": the catalog shows it struck through with the
            // discount. list_price and professional_price are already the offer
            // prices, so carts and orders are not affected.
            $table->decimal('compare_at_price', 12, 2)->nullable()->after('professional_price');
        });

        DB::statement('ALTER TABLE products ADD CONSTRAINT products_compare_at_price_non_negative CHECK (compare_at_price IS NULL OR compare_at_price >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE products DROP CHECK products_compare_at_price_non_negative');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('compare_at_price');
        });
    }
};
