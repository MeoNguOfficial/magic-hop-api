<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserScore extends Model
{
    use HasFactory;

    protected $table = 'user_scores';

    protected $fillable = [
        'user_id',
        'beatmap_id',
        'score',
        'hard_mode_score',
        'is_normal_mode_passed'
    ];

    protected $casts = [
        'beatmap_id' => 'integer',
        'score'      => 'integer',
        'hard_mode_score' => 'integer',
        'is_normal_mode_passed' => 'boolean',
    ];

    /**
     * Liên kết ngược tới bảng Users (Khóa ngoại user_id dạng string ULID)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Liên kết ngược tới bảng Beatmaps (Khóa ngoại beatmap_id dạng BigInteger)
     */
    public function beatmap(): BelongsTo
    {
        return $this->belongsTo(Beatmap::class, 'beatmap_id', 'id');
    }
}
