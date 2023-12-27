<?php

namespace App\Flare\Models;

use Database\Factories\FactionLoyaltyNpcFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactionLoyaltyNpc extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'faction_loyalty_id',
        'npc_id',
        'current_level',
        'max_level',
        'next_level_fame',
        'currently_helping',
        'kingdom_item_defence_bonus'
    ];

    protected $casts = [
        'current_level'              => 'integer',
        'max_level'                  => 'integer',
        'next_level_fame'            => 'integer',
        'currently_helping'          => 'boolean',
        'kingdom_item_defence_bonus' => 'float'
    ];

    protected $appends = [
        'current_fame',
        'current_kingdom_item_defence_bonus'
    ];

    public function faction() {
        return $this->belongsTo(Faction::class, 'faction_id', 'id');
    }

    public function character() {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    public function factionLoyaltyNpcTasks() {
        return $this->hasOne(FactionLoyaltyNpcTask::class);
    }

    public function getCurrentFameAttribute() {
        $this->factionLoyaltyNpcTasks->sum('current_amount');
    }

    public function getCurrentKingdomItemDefenceBonus() {
        return $this->kingdom_item_defence_bonus * $this->current_level;
    }

    protected static function newFactory() {
        return FactionLoyaltyNpcFactory::new();
    }
}
