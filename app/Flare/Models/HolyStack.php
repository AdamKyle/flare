<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HolyStack extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'item_id',
        'devouring_darkness_bonus',
        'stat_increase_bonus',
    ];

    protected $casts = [
        'devouring_darkness_bonus' => 'float',
        'stat_increase_bonus'      => 'float',
    ];

    public function item() {
        return $this->belongsTo(Item::class, 'id', 'item_id');
    }
}
