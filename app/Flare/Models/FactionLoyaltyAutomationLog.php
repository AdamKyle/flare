<?php

namespace App\Flare\Models;

use Database\Factories\FactionLoyaltyAutomationLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactionLoyaltyAutomationLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'faction_loyalty_automation_id',
        'fight_logs',
        'crafting_logs',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'fight_logs' => 'array',
        'crafting_logs' => 'array',
    ];

    public function factionLoyaltyAutomation()
    {
        return $this->belongsTo(FactionLoyaltyAutomation::class, 'faction_loyalty_automation_id', 'id');
    }

    protected static function newFactory()
    {
        return FactionLoyaltyAutomationLogFactory::new();
    }
}
