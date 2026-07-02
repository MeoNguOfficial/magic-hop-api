<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    protected $token;

    // Sử dụng hàm này để truyền Token từ Controller vào Resource
    public function __construct($resource, $token)
    {
        parent::__construct($resource);
        $this->token = $token;
    }

    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->token,
            'token_type'   => 'Bearer',
            'user'         => [
                'id'         => $this->id,
                'username'   => $this->username,
                'realname'   => $this->realname,
                'email'      => $this->email,
                'is_admin'   => (bool) $this->is_admin,
                'is_banned'  => (bool) $this->is_banned,
                'is_actived' => (bool) $this->is_actived,
            ]
        ];
    }
}
