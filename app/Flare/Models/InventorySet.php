<?php

namespace App\Flare\Models;

use Database\Factories\InventorySetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventorySet extends Model
{
    use HasFactory;

    public const BATCH_CRAFTING_SPECIAL_TYPE = 'batch_crafting';

    public const BATCH_CRAFTING_SET_NAME = 'Crafted Items Set';

    public const BATCH_CRAFTING_MAX_SLOTS = 2000;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'character_id',
        'is_equipped',
        'can_be_equipped',
        'special_type',
        'max_slots',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_equipped' => 'boolean',
        'can_be_equipped' => 'boolean',
        'max_slots' => 'integer',
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function slots()
    {
        return $this->hasMany(SetSlot::class);
    }

    public function isBatchCraftingSet(): bool
    {
        return $this->special_type === self::BATCH_CRAFTING_SPECIAL_TYPE;
    }

    public function currentSlotCount(): int
    {
        if ($this->relationLoaded('slots')) {
            return $this->slots->count();
        }

        return $this->slots()->count();
    }

    public function remainingSlots(): int
    {
        if (is_null($this->max_slots)) {
            return PHP_INT_MAX;
        }

        return max(0, $this->max_slots - $this->currentSlotCount());
    }

    protected static function newFactory()
    {
        return InventorySetFactory::new();
    }
}
