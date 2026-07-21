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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 100)->unique();
            $table->foreignId('admin_id')->constrained('users');
            $table->foreignId('operator_id')->constrained('users');
            $table->decimal('total_cost', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->decimal('profit', 15, 2);
            $table->enum('payment_method', ['cash', 'qris', 'transfer']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
