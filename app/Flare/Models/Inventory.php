<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Flare\Models\Item;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\QuestItemSlot;
use Database\Factories\InventoryFactory;

class Inventory extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    public function slots() {
        return $this->hasMany(InventorySlot::class);
    }

    public function questItemSlots() {
        return $this->hasMany(QuestItemSlot::class);
    }

    public function character() {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    protected static function newFactory() {
        return InventoryFactory::new();
    }
}
