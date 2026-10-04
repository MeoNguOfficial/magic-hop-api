<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // We handle authorization via Policy in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'allow_background_music'  => 'sometimes|boolean',
            'is_game_muted'           => 'sometimes|boolean',
            'is_menu_muted'           => 'sometimes|boolean',
            'is_mfx_game_over_muted'  => 'sometimes|boolean',
            'is_play_sfx_muted'       => 'sometimes|boolean',
            'is_pregame_muted'        => 'sometimes|boolean',
            'is_preview_muted'        => 'sometimes|boolean',
            'is_round_muted'          => 'sometimes|boolean',
            'is_sfx_muted'            => 'sometimes|boolean',
            'is_ui_muted'             => 'sometimes|boolean',
            'preserve_pitch_enabled'  => 'sometimes|boolean',
            'game_volume'             => 'sometimes|numeric',
            'menu_volume'             => 'sometimes|numeric',
            'mfx_game_over_volume'    => 'sometimes|numeric',
            'play_sfx_volume'         => 'sometimes|numeric',
            'pregame_volume'          => 'sometimes|numeric',
            'preview_volume'          => 'sometimes|numeric',
            'round_volume'            => 'sometimes|numeric',
            'sfx_volume'              => 'sometimes|numeric',
            'ui_volume'               => 'sometimes|numeric',
            'antialiasing_enabled'    => 'sometimes|boolean',
            'ball_aura_enabled'       => 'sometimes|boolean',
            'ball_trail_enabled'      => 'sometimes|boolean',
            'bg_particles_enabled'    => 'sometimes|boolean',
            'dynamic_colors_enabled'  => 'sometimes|boolean',
            'shockwaves_enabled'      => 'sometimes|boolean',
            'show_boundaries_enabled' => 'sometimes|boolean',
            'tile_animations_enabled' => 'sometimes|boolean',
            'ui_animations_enabled'   => 'sometimes|boolean',
            'visualizer_enabled'      => 'sometimes|boolean',
            'graphics_quality'        => 'sometimes|string',
            'spawn_animation_mode'    => 'sometimes|string',
            'tile_detail_scale'       => 'sometimes|integer',
            'blocks_ahead_limit'      => 'sometimes|integer',
            'blocks_behind_limit'     => 'sometimes|integer',
            'bot_assist_enabled'      => 'sometimes|boolean',
            'invert_controls_enabled' => 'sometimes|boolean',
            'is_relative_pc'          => 'sometimes|boolean',
            'raw_input_enabled'       => 'sometimes|boolean',
            'relax_mode_enabled'      => 'sometimes|boolean',
            'sensitivity'             => 'sometimes|numeric',
            'selected_language'       => 'sometimes|string',
            'selected_song_index'     => 'sometimes|integer',
        ];
    }
}
