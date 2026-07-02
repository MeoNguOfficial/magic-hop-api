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
        Schema::create('account_activations', function (Blueprint $table) {
            $table->id();
            // Lưu email người nhận mã kích hoạt
            $table->string('email')->index();
            // Mã OTP đã băm bảo mật (Hash::make)
            $table->string('otp');
            // Thời gian hết hạn của mã kích hoạt này
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_activations');
    }
};