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
        if (!Schema::hasColumn('user_scores', 'hard_mode_score')) {
            Schema::table('user_scores', function (Blueprint $table) {
                $table->integer('hard_mode_score')->default(0)->after('score');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('user_scores', 'hard_mode_score')) {
            Schema::table('user_scores', function (Blueprint $table) {
                $table->dropColumn('hard_mode_score');
            });
        }
    }
};
