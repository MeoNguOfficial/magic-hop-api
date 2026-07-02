<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function columnExists(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Chuyển đổi cột tokenable_id từ dạng số nguyên lớn (BigInt) sang dạng chuỗi (String) phù hợp với ULID
            $table->string('tokenable_id', 36)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            // Trả lại trạng thái số nguyên ban đầu nếu rollback migration
            $table->unsignedBigInteger('tokenable_id')->change();
        });
    }
};
