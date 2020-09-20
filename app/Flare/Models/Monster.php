<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Flare\Models\Skill;
use Database\Factories\MonsterFactory;

class Monster extends Model
{

    use HasFactory;

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
        'quest_item_id',
        'quest_item_drop_chance',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'xp'                     => 'integer',
        'str'                    => 'integer',
        'dur'                    => 'integer',
        'dex'                    => 'integer',
        'chr'                    => 'integer',
        'int'                    => 'integer',
        'ac'                     => 'integer',
        'gold'                   => 'integer',
        'drop_check'             => 'float',
        'max_level'              => 'integer',
        'quest_item_drop_chance' => 'float',
    ];

    public function skills() {
        return $this->hasMany(Skill::class);
    }

    public function questItem() {
        return $this->hasOne(Item::class, 'id', 'quest_item_id');
    }

    protected static function newFactory() {
        return MonsterFactory::new();
    }
}
