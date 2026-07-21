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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('member_discount_amount');
            $table->enum('member_discount_type', ['fixed', 'percentage'])->default('fixed')->after('is_active');
            $table->decimal('member_discount_value', 10, 2)->default(0)->after('member_discount_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('member_discount_value');
            $table->dropColumn('member_discount_type');
            $table->integer('member_discount_amount')->default(0);
        });
    }
};
