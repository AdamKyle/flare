<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Flare\Models\Item;
use Database\Factories\QuestItemSlotFactory;

class QuestItemSlot extends Model
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
    ];

    protected $casts = [
        'item_id' => 'integer',
    ];

    public function item() {
        return $this->hasOne(Item::class, 'id', 'item_id');
    }

    protected static function newFactory() {
        return QuestItemSlotFactory::new();
    }
}
