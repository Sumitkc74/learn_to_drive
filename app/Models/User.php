<?php

namespace App\Models;

use App\Models\Concerns\TracksCreator;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, InteractsWithMedia, TracksCreator;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phoneNumber',
        'role',
        'is_seed_admin',
        'is_active',
        'suspended_at',
        'suspension_reason',
        'last_login_at',
        'profileImage',
        'phone_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'phone_verification_code',
        'phone_verification_expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'phone_verification_expires_at' => 'datetime',
        'is_seed_admin' => 'boolean',
        'is_active' => 'boolean',
        'suspended_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    public function getAvatarUrlAttribute(): string
    {
        return $this->getFirstMediaUrl() ?: asset('dist/img/avatar.png');
    }

    public function canBeManagedBy(User $actor): bool
    {
        if ($this->is_seed_admin) {
            return false;
        }

        return $actor->is_seed_admin || $this->role !== 'Admin';
    }

    public function histories()
    {
        return $this->hasMany(UserHistory::class);
    }
}
