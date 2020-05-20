<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use App\Flare\Models\Item;

class InventorySlot extends Model
{

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
    ];

    public function item() {
        return $this->hasOne(Item::class, 'id', 'item_id');
    }
}
