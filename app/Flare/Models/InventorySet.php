<?php

namespace App\Flare\Models;

use Database\Factories\InventorySetFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Flare\Models\QuestItemSlot;

class InventorySet extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'is_equipped',
        'can_be_equipped',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_equipped'     => 'boolean',
        'can_be_equipped' => 'boolean',
    ];

    public function character() {
        return $this->belongsTo(Character::class);
    }

    public function slots() {
        return $this->hasMany(SetSlot::class);
    }

    protected static function newFactory() {
        return InventorySetFactory::new();
    }
}
