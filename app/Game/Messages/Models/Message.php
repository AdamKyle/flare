<?php

namespace App\Game\Messages\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;

class Message extends Model
{

    protected $fillable = [
        'user_id',
        'message',
        'from_user',
        'to_user',
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
}
