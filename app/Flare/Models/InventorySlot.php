<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Flare\Models\Item;
use Database\Factories\InventorySlotFactory;

class InventorySlot extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'inventory_id',
        'item_id',
        'equipped',
        'position',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'equipped' => 'boolean',
        'item_id'  => 'integer',
    ];

    public function item() {
        return $this->hasOne(Item::class, 'id', 'item_id');
    }

    protected static function newFactory() {
        return InventorySlotFactory::new();
    }
}
