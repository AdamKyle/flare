<?php

namespace App\Flare\Models;

use Database\Factories\CharacterBoonFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CharacterBoon extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'item_id',
        'last_for_minutes',
        'amount_used',
        'started',
        'complete',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'character_id'                            => 'integer',
        'item_id'                                 => 'integer',
        'started'                                 => 'datetime',
        'complete'                                => 'datetime',
        'last_for_minutes'                        => 'integer',
        'amount_used'                             => 'integer',
    ];

    public function character() {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    public function itemUsed() {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    protected static function newFactory() {
        return CharacterBoonFactory::new();
    }
}
