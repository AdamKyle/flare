<?php

namespace App\Flare\Models;

use Database\Factories\SetSlotFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SetSlot extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'item_id',
        'inventory_set_id',
        'equipped',
        'position',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    public function inventorySet() {
        return $this->belongsTo(InventorySet::class);
    }

    public function item() {
        return $this->hasOne(Item::class, 'id', 'item_id');
    }

    protected static function newFactory() {
        return SetSlotFactory::new();
    }
}
