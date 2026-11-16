<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shipping costs depend on the region and some zones have no fixed price
     * yet: those orders are placed with the shipping "to be agreed" (null) and
     * the store tells the customer the cost afterwards. Zero means free.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('shipping_cost', 12, 2)->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('shipping_cost', 12, 2)->nullable(false)->default(0)->change();
        });
    }
};
