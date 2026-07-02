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
        Schema::create('user_scores', function (Blueprint $table) {
            $table->id();

            // 1. Khóa ngoại liên kết với bảng users (Dùng foreignUlid vì bảng users dùng ULID)
            $table->foreignUlid('user_id')
                  ->constrained('users')
                  ->onDelete('cascade'); // Nếu xóa User, toàn bộ điểm của User đó sẽ tự động xóa theo

            // 2. Khóa ngoại liên kết với bảng beatmaps (Dùng foreignId vì bảng beatmaps dùng ID tự tăng)
            $table->foreignId('beatmap_id')
                  ->constrained('beatmaps')
                  ->onDelete('cascade'); // Nếu xóa bài nhạc, dữ liệu bảng xếp hạng của bài đó tự động xóa theo

            $table->integer('score'); // Điểm số đạt được ván đó
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_scores');
    }
};
