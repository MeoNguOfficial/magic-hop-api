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
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();

            // Khóa ngoại liên kết với bảng users (Dùng foreignUlid vì bảng users dùng ULID)
            $table->foreignUlid('user_id')->constrained('users')->onDelete('cascade');

            // --- AUDIO SETTINGS ---
            $table->boolean('allow_background_music')->default(false);
            $table->boolean('is_game_muted')->default(false);
            $table->boolean('is_menu_muted')->default(false);
            $table->boolean('is_mfx_game_over_muted')->default(false);
            $table->boolean('is_play_sfx_muted')->default(false);
            $table->boolean('is_pregame_muted')->default(false);
            $table->boolean('is_preview_muted')->default(false);
            $table->boolean('is_round_muted')->default(false);
            $table->boolean('is_sfx_muted')->default(false);
            $table->boolean('is_ui_muted')->default(false);
            $table->boolean('preserve_pitch_enabled')->default(false);

            $table->float('game_volume')->default(0.8);
            $table->float('menu_volume')->default(0.2);
            $table->float('mfx_game_over_volume')->default(0.8);
            $table->float('play_sfx_volume')->default(0.25);
            $table->float('pregame_volume')->default(0.25);
            $table->float('preview_volume')->default(0.6);
            $table->float('round_volume')->default(0.2);
            $table->float('sfx_volume')->default(0.8);
            $table->float('ui_volume')->default(0.8);

            // --- GRAPHICS & VISUALS ---
            $table->boolean('antialiasing_enabled')->default(false);
            $table->boolean('ball_aura_enabled')->default(true);
            $table->boolean('ball_trail_enabled')->default(true);
            $table->boolean('bg_particles_enabled')->default(true);
            $table->boolean('dynamic_colors_enabled')->default(true);
            $table->boolean('shockwaves_enabled')->default(true);
            $table->boolean('show_boundaries_enabled')->default(true);
            $table->boolean('tile_animations_enabled')->default(true);
            $table->boolean('ui_animations_enabled')->default(true);
            $table->boolean('visualizer_enabled')->default(false);
            $table->string('graphics_quality', 10)->default('fhd');
            $table->string('spawn_animation_mode', 50)->default('slide');
            $table->integer('tile_detail_scale')->default(1);

            // --- GAMEPLAY & CONTROLS ---
            $table->integer('blocks_ahead_limit')->default(8);
            $table->integer('blocks_behind_limit')->default(2);
            $table->boolean('bot_assist_enabled')->default(false);
            $table->boolean('invert_controls_enabled')->default(false);
            $table->boolean('is_relative_pc')->default(true);
            $table->boolean('raw_input_enabled')->default(true);
            $table->boolean('relax_mode_enabled')->default(true);
            $table->float('sensitivity')->default(1.0);

            // --- MISC ---
            $table->string('selected_language', 10)->default('auto');
            $table->integer('selected_song_index')->default(57);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
