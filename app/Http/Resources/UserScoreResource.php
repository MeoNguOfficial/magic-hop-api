<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserScoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'easy_mode_score'       => (int) $this->easy_mode_score,
            'is_easy_mode_passed'   => (bool) $this->is_easy_mode_passed,
            'score'                 => (int) $this->score,
            'is_normal_mode_passed' => (bool) $this->is_normal_mode_passed,
            'hard_mode_score'       => (int) $this->hard_mode_score,
            'is_hard_mode_passed'   => (bool) $this->is_hard_mode_passed,
            'asian_mode_score'      => (int) $this->asian_mode_score,
            'is_asian_mode_passed'  => (bool) $this->is_asian_mode_passed,
            'created_at'            => $this->created_at?->toIso8601String(),

            // Trả về thông tin cơ bản của User đã chơi
            'user' => [
                'id'       => $this->user_id,
                'username' => $this->user?->username,
                'realname' => $this->user?->realname,
            ],

            // Trả về thông tin bài nhạc đã chơi
            'beatmap' => [
                'id'     => $this->beatmap_id,
                'name'   => $this->beatmap?->name,
                'artist' => $this->beatmap?->artist,
            ],
        ];
    }
}
