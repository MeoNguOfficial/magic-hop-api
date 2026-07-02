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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            
            // Liên kết tới phòng chat/ticket hỗ trợ nào
            $table->foreignId('chat_room_id')->constrained('chat_rooms')->onDelete('cascade');
            
            // ID người gửi tin (User, Admin hoặc Trợ lý ảo)
            $table->ulid('sender_id');
            
            // Nội dung đoạn chat
            $table->text('message');
            
            // Loại tin nhắn: text, image, hoặc system (tin nhắn tự động từ hệ thống/bot)
            $table->string('type')->default('text');
            
            // Trạng thái đọc tin nhắn
            $table->boolean('is_read')->default(false);
            
            $table->timestamps();

            // Ràng buộc người gửi
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};