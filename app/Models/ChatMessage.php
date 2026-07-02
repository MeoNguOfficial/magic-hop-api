<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $table = 'chat_messages';

    protected $fillable = [
        'chat_room_id',
        'sender_id',
        'message',
        'type',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    /**
     * Mối quan hệ N-1: Tin nhắn này nằm trong phòng chat nào
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class, 'chat_room_id', 'id');
    }

    /**
     * Mối quan hệ N-1: Ai là người gửi tin nhắn này (User hoặc Admin/Bot)
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id', 'id');
    }
}