<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatRoom extends Model
{
    use HasFactory;

    protected $table = 'chat_rooms';

    protected $fillable = [
        'user_id',
        'assigned_to',
        'type',
        'title',
        'status',
    ];

    /**
     * Mối quan hệ N-1: Phòng chat này thuộc về người chơi nào
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Mối quan hệ N-1: Phòng chat này được tiếp nhận bởi Admin/Trợ lý nào
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to', 'id');
    }

    /**
     * Mối quan hệ 1-N: Một phòng chat thì có nhiều tin nhắn hội thoại bên trong
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_room_id', 'id');
    }
}