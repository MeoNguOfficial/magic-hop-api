<?php

namespace App\Models;

// 1. CHẮC CHẮN PHẢI IMPORT DÒNG THƯ VIỆN NÀY
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class User extends Authenticatable
{
    // 2. KHAI BÁO TRAIT HasApiTokens VÀO ĐÂY ĐỂ KÍCH HOẠT HÀM createToken()
    use HasApiTokens, HasFactory, Notifiable, HasUlids;

    protected $table = 'users';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'username',
        'realname',
        'password',
        'email',
        'phone',
        'is_admin',
        'is_actived',
        
        // Bổ sung các trường quản lý lock/ban vào fillable
        'login_attempts',
        'is_locked',
        'locked_until',
        'is_banned',
        'banned_until',
        'banned_reason', // Lý do bị khóa
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password'       => 'hashed',
        'is_admin'       => 'boolean',
        'is_actived'     => 'boolean',
        'is_locked'      => 'boolean',
        'is_banned'      => 'boolean',
        
        // Cast các trường thời gian về Carbon instance để tiện cộng/trừ thời gian
        'login_attempts' => 'integer',
        'locked_until'   => 'datetime',
        'banned_until'   => 'datetime',
        'banned_reason'  => 'string',
    ];

    /**
     * Mối quan hệ 1-1 tới bảng thiết lập game
     */
    public function setting(): HasOne
    {
        return $this->hasOne(UserSetting::class, 'user_id', 'id');
    }

    /**
     * Mối quan hệ 1-N tới bảng lưu điểm số ván chơi
     */
    public function scores(): HasMany
    {
        return $this->hasMany(UserScore::class, 'user_id', 'id');
    }
}
