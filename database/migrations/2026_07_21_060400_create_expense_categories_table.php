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
        // Buat tabel kategori pengeluaran milik admin
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        // Hapus kolom category dari expenses (tidak dipakai lagi)
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        // Tambah expense_category_id sebagai FK (nullable agar data lama aman)
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('expense_category_id')->nullable()->after('admin_id')->constrained('expense_categories')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
            $table->dropColumn('expense_category_id');
            $table->string('category')->after('admin_id')->default('Lain-lain');
        });

        Schema::dropIfExists('expense_categories');
    }
};
