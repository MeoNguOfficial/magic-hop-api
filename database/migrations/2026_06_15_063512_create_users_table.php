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
        Schema::create('users', function (Blueprint $table) {
            // Sử dụng ULID làm khóa chính thay cho ID tăng tự động thông thường
            $table->ulid('id')->primary();

            $table->string('username')->unique();
            $table->string('realname')->nullable(); // optional
            $table->string('password');
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();

            // Các trường trạng thái phân quyền và kích hoạt
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_actived')->default(true); // Mặc định hoạt khi tạo mới, sẽ update false khi tính năng mail được triển khai

            // Xử lý khóa do nhập sai quá nhiều lần
            $table->integer('login_attempts')->default(0); // Đếm số lần nhập sai
            $table->boolean('is_locked')->default(false); 
            $table->timestamp('locked_until')->nullable(); // Thời gian hết hạn khóa

            // Xử lý block do check hack game
            $table->boolean('is_banned')->default(false); 
            $table->timestamp('banned_until')->nullable();
            $table->string('banned_reason')->nullable(); // Lý do cấm

            // Tự động tạo 2 trường created_at và updated_at
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
