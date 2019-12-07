<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use App\Flare\Models\Skill;
use App\Flare\Models\Drop;

class Monster extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'damage_stat',
        'xp',
        'str',
        'dur',
        'dex',
        'chr',
        'int',
        'ac',
        'gold',
        'max_level',
        'health_range',
        'attack_range',
        'drop_check',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'xp'         => 'integer',
        'str'        => 'integer',
        'dur'        => 'integer',
        'dex'        => 'integer',
        'chr'        => 'integer',
        'int'        => 'integer',
        'ac'         => 'integer',
        'gold'       => 'integer',
        'drop_check' => 'integer',
        'max_level'  => 'integer',
    ];

    public function skills() {
        return $this->hasMany(Skill::class);
    }
}
