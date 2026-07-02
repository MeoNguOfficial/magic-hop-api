<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatRoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->user_id,
            'assigned_to' => $this->assigned_to,
            'type'        => $this->type,   // forgot_password, technical, account_issue...
            'title'       => $this->title,
            'status'      => $this->status, // pending, open, resolved, closed
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),

            // Load thông tin người tạo ticket (User)
            'user'        => $this->whenLoaded('user', function() {
                return [
                    'username' => $this->user->username,
                    'realname' => $this->user->realname,
                    'email'    => $this->user->email,
                ];
            }),

            // Load thông tin Admin/Trợ lý đang xử lý
            'assignee'    => $this->whenLoaded('assignee', function() {
                return [
                    'username' => $this->assignee->username,
                    'realname' => $this->assignee->realname,
                ];
            }),

            // Load danh sách các tin nhắn hội thoại bên trong phòng chat này
            'messages'    => ChatMessageResource::collection($this->whenLoaded('messages')),
            
            // Đếm số tin nhắn chưa đọc trong phòng chat này (tiện show badge cho admin/user)
            'unread_count'=> $this->when($this->relationLoaded('messages'), function() use ($request) {
                $currentUser = $request->user();
                if (!$currentUser) return 0;
                
                return $this->messages
                    ->where('is_read', false)
                    ->where('sender_id', '!=', $currentUser->id)
                    ->count();
            }),
        ];
    }
}