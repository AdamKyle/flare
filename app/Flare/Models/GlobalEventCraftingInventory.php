<?php

namespace App\Flare\Models;

use Database\Factories\GlobalEventCraftingInventoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalEventCraftingInventory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'global_event_id',
        'character_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'global_event_id' => 'integer',
        'character_id' => 'integer',
    ];

    protected $appends = [
        'total_kills',
    ];

    public function globalEvent()
    {
        return $this->belongsTo(GlobalEventGoal::class, 'global_event_id', 'id');
    }

    public function craftingSlots()
    {
        return $this->hasMany(GlobalEventCraftingInventorySlot::class, 'global_event_crafting_inventory_id', 'id');
    }

    public function character()
    {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    protected static function newFactory()
    {
        return GlobalEventCraftingInventoryFactory::new();
    }
}
