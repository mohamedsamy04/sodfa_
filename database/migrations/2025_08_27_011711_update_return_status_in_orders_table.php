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
        Schema::table('orders', function (Blueprint $table) {
            // تغيير العمود من enum لـ string مع الافتراضي 'processing'
            $table->string('return_status')->default('processing')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // لو حبيت ترجع للـ enum القديم
            $table->enum('return_status', ['pending', 'approved', 'rejected'])->default('pending')->change();
        });
    }
};
