<?php

namespace App\Flare\Models;

use Database\Factories\ExplorationLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExplorationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id',
        'user_id',
        'character_automation_id',
        'monster_id',
        'attack_type',
        'started_at',
        'ended_at',
        'stopped_reason',
        'stopped_by_player',
        'fights',
        'kills',
        'weapon_damage',
        'spell_damage',
        'xp_gained',
        'skill_xp_gained',
        'faction_points_gained',
        'currencies_gained',
        'summary',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'stopped_by_player' => 'boolean',
        'fights' => 'integer',
        'kills' => 'integer',
        'weapon_damage' => 'integer',
        'spell_damage' => 'integer',
        'xp_gained' => 'integer',
        'skill_xp_gained' => 'integer',
        'faction_points_gained' => 'integer',
        'currencies_gained' => 'array',
        'summary' => 'array',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function characterAutomation(): BelongsTo
    {
        return $this->belongsTo(CharacterAutomation::class);
    }

    protected static function newFactory(): ExplorationLogFactory
    {
        return ExplorationLogFactory::new();
    }
}
