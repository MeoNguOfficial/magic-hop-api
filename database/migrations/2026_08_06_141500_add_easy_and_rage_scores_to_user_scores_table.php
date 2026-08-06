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
            if (!Schema::hasColumn('user_scores', 'easy_mode_score')) {
                $table->integer('easy_mode_score')->default(0)->after('score');
            }
            if (!Schema::hasColumn('user_scores', 'is_easy_mode_passed')) {
                $table->boolean('is_easy_mode_passed')->default(false)->after('easy_mode_score');
            }
            if (!Schema::hasColumn('user_scores', 'is_hard_mode_passed')) {
                $table->boolean('is_hard_mode_passed')->default(false)->after('hard_mode_score');
            }
            if (!Schema::hasColumn('user_scores', 'asian_mode_score')) {
                $table->integer('asian_mode_score')->default(0)->after('is_hard_mode_passed');
            }
            if (!Schema::hasColumn('user_scores', 'is_asian_mode_passed')) {
                $table->boolean('is_asian_mode_passed')->default(false)->after('asian_mode_score');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_scores', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('user_scores', 'easy_mode_score')) $columnsToDrop[] = 'easy_mode_score';
            if (Schema::hasColumn('user_scores', 'is_easy_mode_passed')) $columnsToDrop[] = 'is_easy_mode_passed';
            if (Schema::hasColumn('user_scores', 'is_hard_mode_passed')) $columnsToDrop[] = 'is_hard_mode_passed';
            if (Schema::hasColumn('user_scores', 'asian_mode_score')) $columnsToDrop[] = 'asian_mode_score';
            if (Schema::hasColumn('user_scores', 'is_asian_mode_passed')) $columnsToDrop[] = 'is_asian_mode_passed';

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
