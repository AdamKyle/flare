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
        'last_automation_action',
        'last_automation_action_at',
        'last_fight_monster_id',
        'last_fight_outcome',
        'last_fight_was_bounty_target',
        'last_fight_was_training',
        'last_fight_stalled_attempt',
        'trained_failed_bounty_monster_id',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'last_automation_action_at' => 'datetime',
        'last_fight_monster_id' => 'integer',
        'last_fight_was_bounty_target' => 'boolean',
        'last_fight_was_training' => 'boolean',
        'last_fight_stalled_attempt' => 'integer',
        'trained_failed_bounty_monster_id' => 'integer',
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
