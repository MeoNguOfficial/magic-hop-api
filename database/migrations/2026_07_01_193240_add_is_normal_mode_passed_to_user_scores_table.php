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
        Schema::table('user_scores', function (Blueprint $table) {
            // Thêm cột boolean, mặc định là false và đặt nó đứng sau cột score cho đẹp cấu trúc database
            $table->boolean('is_normal_mode_passed')
                  ->default(false)
                  ->after('score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_scores', function (Blueprint $table) {
            // Xóa cột nếu chẳng may muốn rollback
            $table->dropColumn('is_normal_mode_passed');
        });
    }
};