<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'username'       => $this->username,
            'realname'       => $this->realname,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'is_admin'       => (bool) $this->is_admin,
            'is_actived'     => (bool) $this->is_actived,
            
            // Bổ sung các trường liên quan đến bảo mật & trạng thái khóa tài khoản
            'login_attempts' => (int) $this->login_attempts,
            'is_locked'      => (bool) $this->is_locked,
            'locked_until'   => $this->locked_until?->toIso8601String(),
            'is_banned'      => (bool) $this->is_banned,
            'banned_until'   => $this->banned_until?->toIso8601String(),
            'banned_reason'  => $this->banned_reason,
            
            // Chỉ load kèm setting nếu controller có gọi eager load with('setting')
            'setting'        => new UserSettingResource($this->whenLoaded('setting')),
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}