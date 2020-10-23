<?php

namespace App\Flare\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;
use App\Flare\Models\Character;
use App\Game\Messages\Models\Message;
use Database\Factories\UserFactory;

class User extends Authenticatable
{
    use Notifiable, HasRoles, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 
        'password',
        'game_key', 
        'private_game_key',
        'message_throttle_count',
        'can_speak_again_at',
        'is_silenced',
        'is_banned',
        'unbanned_at',
        'ip_address',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at'      => 'datetime',
        'can_speak_again_at'     => 'datetime',
        'is_silenced'            => 'boolean',
        'message_throttle_count' => 'integer',
        'is_banned'              => 'boolean',
        'unbanned_at'            => 'datetime',
    ];

    public function character() {
        return $this->hasOne(Character::class);
    }

    public function messages() {
        return $this->hasMany(Message::class);
    }

    protected static function newFactory() {
        return UserFactory::new();
    }
}
