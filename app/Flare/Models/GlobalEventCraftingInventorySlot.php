<?php

namespace App\Flare\Models;

use App\Game\Events\Values\EventType;
use App\Flare\Values\ItemSpecialtyType;
use Database\Factories\GlobalEventGoalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlobalEventCraftingInventorySlot extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'global_event_crafting_inventory_id',
        'item_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'global_event_id'  => 'integer',
        'character_id'     => 'integer',
    ];

    public function item() {
        return $this->hasOne(Item::class, 'id', 'item_id');
    }

    public function inventory() {
        return $this->belongsTo(GlobalEventCraftingInventory::class, 'global_event_crafting_inventory_id', 'id');
    }

    protected static function newFactory() {
    }
}
