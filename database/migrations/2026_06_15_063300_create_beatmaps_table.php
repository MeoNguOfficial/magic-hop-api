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
        Schema::create('beatmaps', function (Blueprint $table) {
            $table->id(); // Khóa chính tự tăng (hoặc $table->ulid('id')->primary() nếu bạn muốn đồng bộ ULID giống bảng users)

            $table->string('name');
            $table->string('artist')->default('Unknown Artist');
            $table->integer('speed')->nullable()->default(18);
            $table->string('genre')->nullable();
            $table->integer('bpm')->nullable();
            $table->string('copyright_status')->nullable();
            $table->boolean('no_fake_block')->default(false);
            $table->string('url'); // Link file .mp3
            $table->string('warning_alert')->nullable();

            // Quản lý thời gian hiển thị (Dùng kiểu date hoặc dateTime tùy nhu cầu của bạn)
            $table->date('day_show')->nullable();
            $table->date('day_hide')->nullable();
            $table->boolean('is_available')->default(true);

            // Trường lưu mảng các nốt nhạc / nhịp nhảy
            $table->json('beats');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beatmaps');
    }
};
