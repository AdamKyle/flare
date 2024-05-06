<?php

namespace App\Flare\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;
use App\Game\Messages\Models\Message;
use Database\Factories\UserFactory;

class User extends Authenticatable {

    use Notifiable, HasRoles, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'password',
        'last_logged_in',
        'message_throttle_count',
        'can_speak_again_at',
        'is_silenced',
        'is_banned',
        'unbanned_at',
        'ip_address',
        'banned_reason',
        'un_ban_request',
        'ignored_unban_request',
        'upgraded_building_email',
        'show_unit_recruitment_messages',
        'show_building_upgrade_messages',
        'show_kingdom_update_messages',
        'show_building_rebuilt_messages',
        'show_monster_to_low_level_message',
        'auto_disenchant',
        'disable_attack_type_popover',
        'auto_disenchant_amount',
        'timeout_until',
        'will_be_deleted',
        'guide_enabled',
        'chat_text_color',
        'chat_is_bold',
        'chat_is_italic',
        'name_tag'
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
        'email_verified_at'              => 'datetime',
        'can_speak_again_at'             => 'datetime',
        'last_logged_in'                 => 'datetime',
        'is_silenced'                    => 'boolean',
        'ignored_unban_request'          => 'boolean',
        'message_throttle_count'         => 'integer',
        'is_banned'                      => 'boolean',
        'unbanned_at'                    => 'datetime',
        'timeout_until'                  => 'datetime',
        'show_unit_recruitment_messages' => 'boolean',
        'show_building_upgrade_messages' => 'boolean',
        'show_kingdom_update_messages'   => 'boolean',
        'show_building_rebuilt_messages' => 'boolean',
        'show_monster_to_low_level_message' => 'boolean',
        'auto_disenchant'                => 'boolean',
        'disable_attack_type_popover'    => 'boolean',
        'will_be_deleted'                => 'boolean',
        'guide_enabled'                  => 'boolean',
        'chat_is_bold'                   => 'boolean',
        'chat_is_italic'                 => 'boolean',
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
