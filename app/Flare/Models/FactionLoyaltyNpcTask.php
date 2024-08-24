<?php

namespace App\Flare\Models;

use Database\Factories\FactionLoyaltyNpcTaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactionLoyaltyNpcTask extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'faction_loyalty_id',
        'faction_loyalty_npc_id',
        'fame_tasks',
    ];

    protected $casts = [
        'fame_tasks' => 'array',
    ];

    protected $appends = [
        'current_amount',
    ];

    public function factionLoyalty()
    {
        return $this->belongsTo(FactionLoyalty::class, 'faction_loyalty_id', 'id');
    }

    public function factionLoyaltyNpc()
    {
        return $this->belongsTo(FactionLoyaltyNpc::class, 'faction_loyalty_npc_id', 'id');
    }

    public function getCurrentAmountAttribute()
    {
        return collect($this->fame_tasks)->sum('amount_completed');
    }

    protected static function newFactory()
    {
        return FactionLoyaltyNpcTaskFactory::new();
    }
}
