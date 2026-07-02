<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    use HasFactory;

    protected $table = 'user_settings';

    protected $fillable = [
        'user_id',
        'allow_background_music',
        'is_game_muted',
        'is_menu_muted',
        'is_mfx_game_over_muted',
        'is_play_sfx_muted',
        'is_pregame_muted',
        'is_preview_muted',
        'is_round_muted',
        'is_sfx_muted',
        'is_ui_muted',
        'preserve_pitch_enabled',
        'game_volume',
        'menu_volume',
        'mfx_game_over_volume',
        'play_sfx_volume',
        'pregame_volume',
        'preview_volume',
        'round_volume',
        'sfx_volume',
        'ui_volume',
        'antialiasing_enabled',
        'ball_aura_enabled',
        'ball_trail_enabled',
        'bg_particles_enabled',
        'dynamic_colors_enabled',
        'shockwaves_enabled',
        'show_boundaries_enabled',
        'tile_animations_enabled',
        'ui_animations_enabled',
        'visualizer_enabled',
        'graphics_quality',
        'spawn_animation_mode',
        'tile_detail_scale',
        'blocks_ahead_limit',
        'blocks_behind_limit',
        'bot_assist_enabled',
        'invert_controls_enabled',
        'is_relative_pc',
        'raw_input_enabled',
        'relax_mode_enabled',
        'sensitivity',
        'selected_language',
        'selected_song_index',
    ];

    protected $casts = [
        // Audio Booleans
        'allow_background_music' => 'boolean',
        'is_game_muted' => 'boolean',
        'is_menu_muted' => 'boolean',
        'is_mfx_game_over_muted' => 'boolean',
        'is_play_sfx_muted' => 'boolean',
        'is_pregame_muted' => 'boolean',
        'is_preview_muted' => 'boolean',
        'is_round_muted' => 'boolean',
        'is_sfx_muted' => 'boolean',
        'is_ui_muted' => 'boolean',
        'preserve_pitch_enabled' => 'boolean',

        // Audio Volumes (Floats)
        'game_volume' => 'float',
        'menu_volume' => 'float',
        'mfx_game_over_volume' => 'float',
        'play_sfx_volume' => 'float',
        'pregame_volume' => 'float',
        'preview_volume' => 'float',
        'round_volume' => 'float',
        'sfx_volume' => 'float',
        'ui_volume' => 'float',

        // Graphics Booleans
        'antialiasing_enabled' => 'boolean',
        'ball_aura_enabled' => 'boolean',
        'ball_trail_enabled' => 'boolean',
        'bg_particles_enabled' => 'boolean',
        'dynamic_colors_enabled' => 'boolean',
        'shockwaves_enabled' => 'boolean',
        'show_boundaries_enabled' => 'boolean',
        'tile_animations_enabled' => 'boolean',
        'ui_animations_enabled' => 'boolean',
        'visualizer_enabled' => 'boolean',
        'tile_detail_scale' => 'integer',

        // Gameplay
        'blocks_ahead_limit' => 'integer',
        'blocks_behind_limit' => 'integer',
        'bot_assist_enabled' => 'boolean',
        'invert_controls_enabled' => 'boolean',
        'is_relative_pc' => 'boolean',
        'raw_input_enabled' => 'boolean',
        'relax_mode_enabled' => 'boolean',
        'sensitivity' => 'float',
        'selected_song_index' => 'integer',
    ];

    /**
     * Liên kết ngược lại Model User (Sử dụng chuỗi ULID)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
