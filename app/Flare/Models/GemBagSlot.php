<?php

namespace App\Flare\Models;

use App\Game\Skills\Values\GemTierValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GemBagSlot extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'gem_bag_id',
        'gem_id',
        'amount',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'character_id' => 'integer',
        'gem_id'       => 'integer',
        'amount'       => 'integer',
    ];

    public function gem(): HasOne {
        return $this->hasOne(Gem::class, 'id', 'gem_id');
    }

    public function GemBag(): BelongsTo {
        return $this->belongsTo(GemBag::class, 'gem_bag_id', 'id');
    }
}
