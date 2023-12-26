<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class FactionLoyaltyNpcTask extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'faction_loyalty_id',
        'faction_loyalty_npc_id',
        'currently_helping',
        'fame_tasks',
    ];

    protected $casts = [
        'currently_helping' => 'boolean',
        'fame_tasks'        => 'array',
    ];

    protected $appends = [
        'current_amount',
    ];

    public function factionLoyalty() {
        return $this->belongsTo(FactionLoyalty::class, 'faction_loyalty_id', 'id');
    }

    public function factionLoyaltyNpc() {
        return $this->belongsTo(FactionLoyaltyNpc::class, 'faction_loyalty_npc_id', 'id');
    }

    public function getCurrentAmountAttribute() {
        return collect($this->fame_tasks)->sum('amount_completed');
    }
}
