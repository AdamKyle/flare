<?php

namespace App\Flare\Models;

use App\Game\Core\Traits\UpdateMarketBoard;
use Database\Factories\InventoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory, UpdateMarketBoard;

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

    public function slots()
    {
        return $this->hasMany(InventorySlot::class);
    }

    public function character()
    {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    protected static function newFactory()
    {
        return InventoryFactory::new();
    }
}
