<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserScoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'score'      => (int) $this->score,
            'is_normal_mode_passed' => (bool) $this->is_normal_mode_passed,
            'created_at' => $this->created_at?->toIso8601String(),

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
