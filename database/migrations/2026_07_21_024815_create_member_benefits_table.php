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
        Schema::create('member_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->integer('min_purchase')->default(0);
            $table->integer('discount_nominal')->default(0);
            $table->integer('cashback_nominal')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'member_discount_type')) {
                $table->dropColumn('member_discount_type');
            }
            if (Schema::hasColumn('users', 'member_discount_value')) {
                $table->dropColumn('member_discount_value');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_benefits');

        Schema::table('users', function (Blueprint $table) {
            $table->enum('member_discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('member_discount_value', 10, 2)->default(0);
        });
    }
};
