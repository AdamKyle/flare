<?php

namespace App\Flare\Models;

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
    protected $casts = [];

    public function character() {
        return $this->belongsTo(Character::class);
    }

    public function slots() {
        return $this->hasMany(SetSlot::class);
    }
}
