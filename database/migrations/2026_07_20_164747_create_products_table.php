<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('sku', 100);
            $table->string('name');
            $table->decimal('cost_price', 15, 2)->unsigned();
            $table->decimal('selling_price', 15, 2)->unsigned();
            $table->integer('stock')->unsigned()->default(0);
            $table->integer('min_stock_alert')->default(5);
            $table->timestamps();

            $table->unique(['admin_id', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
