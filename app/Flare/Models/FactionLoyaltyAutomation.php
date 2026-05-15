<?php

namespace App\Flare\Models;

use Database\Factories\FactionLoyaltyAutomationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactionLoyaltyAutomation extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_automation_id',
        'character_id',
        'faction_loyalty_npc_id',
        'failed_bounty_monster_id',
        'failed_crafting_item_id',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function characterAutomation()
    {
        return $this->belongsTo(CharacterAutomation::class);
    }

    public function log()
    {
        return $this->hasOne(FactionLoyaltyAutomationLog::class);
    }

    public function factionLoyaltyNpc() {
        return $this->belongsTo(FactionLoyaltyNpc::class);
    }

    public function failedBountyMonster() {
        return $this->belongsTo(Monster::class, 'failed_bounty_monster_id', 'id');
    }

    public function failedFactionCraftingItem() {
        return $this->belongsTo(Item::class, 'failed_crafting_item_id', 'id');
    }

    protected static function newFactory()
    {
        return FactionLoyaltyAutomationFactory::new();
    }
}
