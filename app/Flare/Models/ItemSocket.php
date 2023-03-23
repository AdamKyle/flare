<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class ItemSocket extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'item_id',
        'gem_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'item_id' => 'integer',
        'gem_id'  => 'integer',
    ];

    public function item() {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function gem() {
        return $this->belongsTo(Gem::class, 'gem_id', 'id');
    }
}
