<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\GameClassFactory;
use App\Flare\Models\Traits\WithSearch;

class GameClass extends Model
{

    use HasFactory, WithSearch;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'damage_stat',
        'str_mod',
        'dur_mod',
        'dex_mod',
        'chr_mod',
        'int_mod',
        'accuracy_mod',
        'dodge_mod',
        'deffense_mod',
        'looting_mod',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'str_mod'      => 'float',
        'dur_mod'      => 'float',
        'dex_mod'      => 'float',
        'chr_mod'      => 'float',
        'int_mod'      => 'float',
        'accuracy_mod' => 'float',
        'dodge_mod'    => 'float',
        'deffense_mod' => 'float',
        'looting_mod'  => 'float',
    ];

    public function gameSkills() {
        return $this->hasMany(GameSkill::class, 'game_class_id', 'id');
    }

    protected static function newFactory() {
        return GameClassFactory::new();
    }
}
