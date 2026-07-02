<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'chat_room_id' => $this->chat_room_id,
            'sender_id'    => $this->sender_id,
            'message'      => $this->message,
            'type'         => $this->type, // text, image, system
            'is_read'      => (bool) $this->is_read,
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
            
            // Nạp kèm thông tin người gửi ngắn gọn nếu được eager load
            'sender'       => $this->whenLoaded('sender', function() {
                return [
                    'username' => $this->sender->username,
                    'realname' => $this->sender->realname,
                    'is_admin' => (bool) $this->sender->is_admin,
                ];
            }),
        ];
    }
}