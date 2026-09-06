<?php

namespace App\Flare\Models;

use App\Game\Character\Values\CharacterClass;
use Database\Factories\GameClassFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameClass extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'damage_stat',
        'to_hit_stat',
        'str_mod',
        'dur_mod',
        'dex_mod',
        'chr_mod',
        'int_mod',
        'agi_mod',
        'focus_mod',
        'accuracy_mod',
        'dodge_mod',
        'defense_mod',
        'looting_mod',
        'primary_required_class_id',
        'secondary_required_class_id',
        'primary_required_class_level',
        'secondary_required_class_level',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'str_mod' => 'integer',
        'dur_mod' => 'integer',
        'dex_mod' => 'integer',
        'chr_mod' => 'integer',
        'int_mod' => 'integer',
        'agi_mod' => 'integer',
        'focus_mod' => 'integer',
        'primary_required_class_id' => 'integer',
        'secondary_required_class_id' => 'integer',
        'primary_required_class_level' => 'integer',
        'secondary_required_class_level' => 'integer',
        'accuracy_mod' => 'float',
        'dodge_mod' => 'float',
        'defense_mod' => 'float',
        'looting_mod' => 'float',
    ];

    /**
     * The Game Skills belonging to this Class.
     *
     * @return HasMany
     */
    public function gameSkills()
    {
        return $this->hasMany(GameSkill::class, 'game_class_id', 'id');
    }

    /**
     * The primary prerequisite Class required to unlock this Class.
     *
     * @return HasOne
     */
    public function primaryClassRequired()
    {
        return $this->hasOne(GameClass::class, 'id', 'primary_required_class_id');
    }

    /**
     * The secondary prerequisite Class required to unlock this Class.
     *
     * @return HasOne
     */
    public function secondaryClassRequired()
    {
        return $this->hasOne(GameClass::class, 'id', 'secondary_required_class_id');
    }

    /**
     * Resolve this Class's name into its CharacterClass enum case.
     *
     * @return CharacterClass
     */
    public function type()
    {
        return CharacterClass::from($this->name);
    }

    /**
     * Resolve the factory used to create new Class instances.
     *
     * @return GameClassFactory
     */
    protected static function newFactory()
    {
        return GameClassFactory::new();
    }
}
