<?php

namespace App\Flare\Models;

use Database\Factories\DelveExplorationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DelveExploration extends Model
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
        'started_at',
        'completed_at',
        'attack_type',
        'increase_enemy_strength',
        'battle_messages',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'increase_enemy_strength' => 'float',
        'battle_messages' => 'array'
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
        return DelveExplorationFactory::new();
    }
}
