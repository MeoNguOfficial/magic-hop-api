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
        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->id();
            
            // Người chơi gửi yêu cầu hỗ trợ (ULID)
            $table->ulid('user_id');
            
            // Người tiếp nhận xử lý: Admin hoặc Nick Trợ lý ảo (Bot)
            $table->ulid('assigned_to')->nullable();
            
            // PHÂN LOẠI HỖ TRỢ: 'forgot_password', 'change_password', 'delete_account', 'technical', 'account_issue', v.v.
            $table->string('type')->default('technical');
            
            // Tiêu đề tóm tắt ngắn gọn lỗi hoặc nội dung yêu cầu
            $table->string('title')->nullable();
            
            // Trạng thái xử lý ticket: pending (chờ), open (đang chat), resolved (đã xử lý xong), closed (đã đóng phòng)
            $table->string('status')->default('pending');
            
            $table->timestamps();

            // Ràng buộc dữ liệu sang bảng users
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_rooms');
    }
};