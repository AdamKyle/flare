<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Flare\Models\Traits\WithSearch;
use Database\Factories\MarketHistoryFactory;

class MarketHistory extends Model
{

    use HasFactory, WithSearch;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'item_id',
        'sold_for'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'sold_for' => 'integer',
    ];

    public function item() {
        return $this->belongsTo(Item::class);
    }

    protected static function newFactory() {
        return MarketHistoryFactory::new();
    }
    
}
