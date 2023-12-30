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

    public function factionLoyalty() {
        return $this->belongsTo(FactionLoyalty::class, 'faction_loyalty_id', 'id');
    }

    public function npc() {
        return $this->belongsTo(Npc::class, 'npc_id', 'id');
    }

    public function factionLoyaltyNpcTasks() {
        return $this->hasOne(FactionLoyaltyNpcTask::class);
    }

    public function getCurrentFameAttribute() {
        return collect($this->factionLoyaltyNpcTasks->fame_tasks)->sum('current_amount');
    }

    public function getCurrentKingdomItemDefenceBonus() {
        return $this->kingdom_item_defence_bonus * $this->current_level;
    }

    protected static function newFactory() {
        return FactionLoyaltyNpcFactory::new();
    }
}
