<?php

namespace App\Flare\Models;

use Database\Factories\WeeklyMonsterFightFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyMonsterFight extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'monster_id',
        'character_deaths',
        'monster_was_killed',
        'reward_processed_at',
    ];

    protected $casts = [
        'character_deaths' => 'integer',
        'monster_was_killed' => 'boolean',
        'reward_processed_at' => 'datetime',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function monster()
    {
        return $this->belongsTo(Monster::class);
    }

    protected static function newFactory()
    {
        return WeeklyMonsterFightFactory::new();
    }
}
