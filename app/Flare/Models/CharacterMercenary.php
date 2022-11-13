<?php

namespace App\Flare\Models;

use App\Game\Mercenaries\Values\MercenaryValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CharacterMercenary extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'mercenary_type',
        'current_level',
        'current_xp',
        'xp_required',
        'reincarnated_bonus',
        'xp_increase',
        'times_reincarnated',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'current_level'      => 'integer',
        'current_xp'         => 'integer',
        'xp_required'        => 'integer',
        'times_reincarnated' => 'integer',
        'reincarnated_bonus' => 'float',
        'xp_increase'        => 'float',
    ];

    public function type(): MercenaryValue {
        return new MercenaryValue($this->mercenary_type);
    }

    public function character() {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }
}
