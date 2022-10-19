<?php

namespace App\Game\Messages\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\MessageFactory;
use App\Flare\Models\User;

class Message extends Model {

    use HasFactory;

    protected $fillable = [
        'user_id',
        'message',
        'from_user',
        'to_user',
        'x_position',
        'y_position',
        'color',
        'hide_location',
    ];

    protected $casts = [
        'hide_location' => 'boolean',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function fromUser() {
        return $this->belongsTo(User::class, 'from_user', 'id');
    }

    public function toUser() {
        return $this->belongsTo(User::class, 'to_user', 'id');
    }

    protected static function newFactory() {
        return MessageFactory::new();
    }
}
