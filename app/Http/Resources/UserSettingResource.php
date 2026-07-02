<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,

            // Nhóm lại thành các object con cho client/game engine dễ quản lý
            'audio' => [
                'allow_background_music'  => (bool) $this->allow_background_music,
                'is_game_muted'           => (bool) $this->is_game_muted,
                'is_menu_muted'           => (bool) $this->is_menu_muted,
                'is_mfx_game_over_muted'  => (bool) $this->is_mfx_game_over_muted,
                'is_play_sfx_muted'       => (bool) $this->is_play_sfx_muted,
                'is_pregame_muted'        => (bool) $this->is_pregame_muted,
                'is_preview_muted'        => (bool) $this->is_preview_muted,
                'is_round_muted'          => (bool) $this->is_round_muted,
                'is_sfx_muted'            => (bool) $this->is_sfx_muted,
                'is_ui_muted'             => (bool) $this->is_ui_muted,
                'preserve_pitch_enabled'  => (bool) $this->preserve_pitch_enabled,
                'game_volume'             => (float) $this->game_volume,
                'menu_volume'             => (float) $this->menu_volume,
                'mfx_game_over_volume'    => (float) $this->mfx_game_over_volume,
                'play_sfx_volume'         => (float) $this->play_sfx_volume,
                'pregame_volume'          => (float) $this->pregame_volume,
                'preview_volume'          => (float) $this->preview_volume,
                'round_volume'            => (float) $this->round_volume,
                'sfx_volume'              => (float) $this->sfx_volume,
                'ui_volume'               => (float) $this->ui_volume,
            ],

            'graphics' => [
                'antialiasing_enabled'    => (bool) $this->antialiasing_enabled,
                'ball_aura_enabled'       => (bool) $this->ball_aura_enabled,
                'ball_trail_enabled'      => (bool) $this->ball_trail_enabled,
                'bg_particles_enabled'    => (bool) $this->bg_particles_enabled,
                'dynamic_colors_enabled'  => (bool) $this->dynamic_colors_enabled,
                'shockwaves_enabled'      => (bool) $this->shockwaves_enabled,
                'show_boundaries_enabled' => (bool) $this->show_boundaries_enabled,
                'tile_animations_enabled' => (bool) $this->tile_animations_enabled,
                'ui_animations_enabled'   => (bool) $this->ui_animations_enabled,
                'visualizer_enabled'      => (bool) $this->visualizer_enabled,
                'graphics_quality'        => $this->graphics_quality,
                'spawn_animation_mode'    => $this->spawn_animation_mode,
                'tile_detail_scale'       => (int) $this->tile_detail_scale,
            ],

            'gameplay' => [
                'blocks_ahead_limit'      => (int) $this->blocks_ahead_limit,
                'blocks_behind_limit'     => (int) $this->blocks_behind_limit,
                'bot_assist_enabled'      => (bool) $this->bot_assist_enabled,
                'invert_controls_enabled' => (bool) $this->invert_controls_enabled,
                'is_relative_pc'          => (bool) $this->is_relative_pc,
                'raw_input_enabled'       => (bool) $this->raw_input_enabled,
                'relax_mode_enabled'      => (bool) $this->relax_mode_enabled,
                'sensitivity'             => (float) $this->sensitivity,
            ],

            'misc' => [
                'selected_language'       => $this->selected_language,
                'selected_song_index'     => (int) $this->selected_song_index,
            ]
        ];
    }
}
