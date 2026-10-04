<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isAdmin = $request->user() && $request->user()->is_admin;
        $isSelf = $request->user() && $request->user()->id === $this->id;

        return [
            'id'             => $this->id,
            'username'       => $this->username,
            'realname'       => $this->realname,
            'email'          => $this->when($isSelf || $isAdmin, $this->email),
            'phone'          => $this->when($isSelf || $isAdmin, $this->phone),
            'is_admin'       => $this->when($isAdmin, (bool) $this->is_admin),
            'is_actived'     => (bool) $this->is_actived,
            
            // Các trường nhạy cảm chỉ hiển thị nếu user gọi API là Admin
            'login_attempts' => $this->when($isAdmin, (int) $this->login_attempts),
            'is_locked'      => $this->when($isAdmin, (bool) $this->is_locked),
            'locked_until'   => $this->when($isAdmin, $this->locked_until?->toIso8601String()),
            'is_banned'      => $this->when($isAdmin, (bool) $this->is_banned),
            'banned_until'   => $this->when($isAdmin, $this->banned_until?->toIso8601String()),
            'banned_reason'  => $this->when($isAdmin, $this->banned_reason),
            
            // Chỉ load kèm setting nếu controller có gọi eager load with('setting')
            'setting'        => new UserSettingResource($this->whenLoaded('setting')),
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}