<?php

namespace App\Flare\Models;

use Database\Factories\AlchemyBagSlotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AlchemyBagSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'alchemy_bag_id',
        'character_id',
        'item_id',
        'amount',
    ];

    protected $casts = [
        'alchemy_bag_id' => 'integer',
        'character_id' => 'integer',
        'item_id' => 'integer',
        'amount' => 'integer',
    ];

    public function item(): HasOne
    {
        return $this->hasOne(Item::class, 'id', 'item_id');
    }

    public function alchemyBag(): BelongsTo
    {
        return $this->belongsTo(AlchemyBag::class, 'alchemy_bag_id', 'id');
    }

    protected static function newFactory(): AlchemyBagSlotFactory
    {
        return AlchemyBagSlotFactory::new();
    }
}
