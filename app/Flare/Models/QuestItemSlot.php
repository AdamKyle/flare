<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use App\Flare\Models\Item;

class QuestItemSlot extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'inventory_id',
        'item_id',
    ];

    protected $casts = [
        'item_id' => 'integer',
    ];

    public function item() {
        return $this->hasOne(Item::class, 'id', 'item_id');
    }
}
