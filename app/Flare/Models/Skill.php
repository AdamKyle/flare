<?php

namespace App\Flare\Models;

use App\Game\Skills\Values\SkillTypeValue;
use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Skill extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'game_skill_id',
        'currently_training',
        'is_locked',
        'level',
        'xp',
        'xp_max',
        'xp_towards',
        'skill_type',
        'is_hidden',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'currently_training' => 'boolean',
        'is_locked' => 'boolean',
        'level' => 'integer',
        'xp' => 'integer',
        'xp_max' => 'integer',
        'skill_type' => 'integer',
        'xp_towards' => 'float',
        'is_hidden' => 'boolean',
    ];

    /**
     * The accessors appended to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'name',
        'class_bonus',
        'class_id',
    ];

    /**
     * Resolve this Skill's type from its base Game Skill.
     *
     * @return SkillTypeValue
     */
    public function type(): SkillTypeValue
    {
        return $this->baseSkill->skillType();
    }

    /**
     * The Game Skill this Character Skill is based on.
     *
     * @return BelongsTo
     */
    public function baseSkill(): BelongsTo
    {
        return $this->belongsTo(GameSkill::class, 'game_skill_id', 'id');
    }

    /**
     * The Character who owns this Skill.
     *
     * @return BelongsTo
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * Resolve this Skill's display name from its base Game Skill.
     *
     * @return string
     */
    public function getNameAttribute(): string
    {
        return $this->baseSkill->name;
    }

    /**
     * Resolve this Skill's total Class bonus for its current level.
     *
     * @return float|int
     */
    public function getClassBonusAttribute(): float|int
    {
        if (is_null($this->baseSkill->class_bonus)) {
            return 0;
        }

        return $this->baseSkill->class_bonus * $this->level;
    }

    /**
     * Resolve the Game Class id associated with this Skill's base Game Skill.
     *
     * @return int|null
     */
    public function getClassIdAttribute(): ?int
    {
        return $this->baseSkill->game_class_id;
    }

    /**
     * Resolve this Skill's description from its base Game Skill.
     *
     * @return string|null
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->baseSkill->description;
    }

    /**
     * Resolve this Skill's max level from its base Game Skill.
     *
     * @return int
     */
    public function getMaxLevelAttribute(): int
    {
        return $this->baseSkill->max_level;
    }

    /**
     * Resolve whether this Skill can be trained from its base Game Skill.
     *
     * @return bool
     */
    public function getCanTrainAttribute(): bool
    {
        return $this->baseSkill->can_train;
    }

    /**
     * Determine whether this Skill reduces fight time at its current level.
     *
     * @return bool
     */
    public function getReducesTimeAttribute(): bool
    {
        $value = $this->baseSkill->fight_time_out_mod_bonus_per_level;

        if (is_null($value)) {
            return false;
        }

        return $value > 0;
    }

    /**
     * Resolve this Skill's total unit time reduction at its current level.
     *
     * @return float|int
     */
    public function getUnitTimeReductionAttribute(): float|int
    {
        return $this->baseSkill->unit_time_reduction * $this->level;
    }

    /**
     * Resolve this Skill's total building time reduction at its current level.
     *
     * @return float|int
     */
    public function getBuildingTimeReductionAttribute(): float|int
    {
        return $this->baseSkill->building_time_reduction * $this->level;
    }

    /**
     * Resolve this Skill's total unit movement time reduction at its current level.
     *
     * @return float|int
     */
    public function getUnitMovementTimeReductionAttribute(): float|int
    {
        return $this->baseSkill->unit_movement_time_reduction * $this->level;
    }

    /**
     * Determine whether this Skill reduces movement time at its current level.
     *
     * @return bool
     */
    public function getReducesMovementTimeAttribute(): bool
    {
        $value = $this->baseSkill->move_time_out_mod_bonus_per_level;

        if (is_null($value)) {
            return false;
        }

        return $value > 0;
    }

    /**
     * Resolve the factory used to create new Skill instances.
     *
     * @return SkillFactory
     */
    protected static function newFactory(): SkillFactory
    {
        return SkillFactory::new();
    }
}
