<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\MarketBoardFactory;

class MarketBoard extends Model {

    use HasFactory;

    protected $table = 'market_board';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'item_id',
        'listed_price',
        'is_locked',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'listed_price' => 'integer',
        'is_locked'    => 'boolean',
        'character_id' => 'integer',
    ];

    public function item() {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function character() {
        return $this->hasOne(Character::class, 'id', 'character_id');
    }

    protected static function newFactory() {
        return MarketBoardFactory::new();
    }

}
