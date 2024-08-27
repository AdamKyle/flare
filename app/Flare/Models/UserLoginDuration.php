<?php

namespace App\Flare\Models;

use Database\Factories\UserLoginDurationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLoginDuration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'logged_in_at',
        'logged_out_at',
        'last_activity',
        'duration_in_seconds',
        'last_heart_beat',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'logged_in_at' => 'datetime',
        'logged_out_at' => 'datetime',
        'last_heart_beat' => 'datetime',
        'duration_in_seconds' => 'integer',
        'last_activity' => 'datetime',
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory()
    {
        return UserLoginDurationFactory::new();
    }

}
