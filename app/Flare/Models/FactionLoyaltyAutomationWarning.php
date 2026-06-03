<?php

namespace App\Flare\Models;

use Database\Factories\FactionLoyaltyAutomationWarningFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactionLoyaltyAutomationWarning extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'faction_loyalty_automation_id',
        'faction_loyalty_automation_log_id',
        'faction_loyalty_npc_id',
        'log_type',
        'log_entry_id',
        'type',
        'message',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function factionLoyaltyAutomation()
    {
        return $this->belongsTo(FactionLoyaltyAutomation::class, 'faction_loyalty_automation_id', 'id');
    }

    public function factionLoyaltyNpc()
    {
        return $this->belongsTo(FactionLoyaltyNpc::class, 'faction_loyalty_npc_id', 'id');
    }

    public function factionLoyaltyAutomationLog()
    {
        return $this->belongsTo(FactionLoyaltyAutomationLog::class, 'faction_loyalty_automation_log_id', 'id');
    }

    protected static function newFactory()
    {
        return FactionLoyaltyAutomationWarningFactory::new();
    }
}
