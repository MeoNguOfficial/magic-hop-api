<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BeatmapResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'artist'           => $this->artist,
            'speed'            => (int) $this->speed,
            'genre'            => $this->genre,
            'bpm'              => (int) $this->bpm,
            'copyright_status' => $this->copyright_status,
            'no_fake_block'    => (bool) $this->no_fake_block,
            'url'              => $this->url,
            'warning_alert'    => $this->warning_alert,
            'day_show'         => $this->day_show?->toDateString(),
            'day_hide'         => $this->day_hide?->toDateString(),
            'is_available'     => (bool) $this->is_available,
            'beats'            => $this->beats, // Tự động trả về chuẩn Json array nhờ Model Casts
        ];
    }
}
