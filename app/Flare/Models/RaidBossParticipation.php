<?php

namespace App\Flare\Models;

use Database\Factories\RaidBossParticipationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RaidBossParticipation extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'raid_id',
        'raid_boss_id',
        'attacks_left',
        'damage_dealt',
        'killed_boss',
    ];

    protected $casts = [
        'attacks_left' => 'integer',
        'raid_boss_id' => 'integer',
        'damage_dealt' => 'integer',
        'killed_boss' => 'boolean',
    ];

    public function raid()
    {
        return $this->hasOne(Raid::class, 'id', 'raid_id');
    }

    public function character()
    {
        return $this->hasOne(Character::class, 'id', 'character_id');
    }

    public function raidBoss(): BelongsTo
    {
        return $this->belongsTo(RaidBoss::class);
    }

    protected static function newFactory(): RaidBossParticipationFactory
    {
        return RaidBossParticipationFactory::new();
    }
}
