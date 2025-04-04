<?php

namespace App\Flare\Models;

use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use App\Game\Skills\Values\SkillTypeValue;
use Database\Factories\KingdomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kingdom extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'game_map_id',
        'name',
        'color',
        'max_stone',
        'max_wood',
        'max_clay',
        'max_iron',
        'max_steel',
        'current_stone',
        'current_wood',
        'current_clay',
        'current_iron',
        'current_steel',
        'current_population',
        'max_population',
        'x_position',
        'y_position',
        'current_morale',
        'max_morale',
        'treasury',
        'gold_bars',
        'published',
        'npc_owned',
        'last_walked',
        'protected_until',
        'is_capital',
        'updated_at',
        'auto_walked',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'color' => 'array',
        'max_stone' => 'integer',
        'max_wood' => 'integer',
        'max_clay' => 'integer',
        'max_iron' => 'integer',
        'max_steel' => 'integer',
        'current_steel' => 'integer',
        'current_stone' => 'integer',
        'current_wood' => 'integer',
        'current_clay' => 'integer',
        'current_iron' => 'integer',
        'current_population' => 'integer',
        'max_population' => 'integer',
        'x_position' => 'integer',
        'y_position' => 'integer',
        'current_morale' => 'float',
        'max_morale' => 'float',
        'treasury' => 'integer',
        'gold_bars' => 'integer',
        'published' => 'boolean',
        'npc_owned' => 'boolean',
        'is_capital' => 'boolean',
        'auto_walked' => 'boolean',
        'last_walked' => 'datetime',
        'protected_until' => 'datetime',
    ];

    /**
     * Update the last walked automatically.
     */
    public function updateLastWalked()
    {
        $this->update([
            'last_walked' => now(),
        ]);
    }

    public function fetchDefenceBonusFromPassive(): float
    {
        return $this->getPercentageForPassive(PassiveSkillTypeValue::KINGDOM_DEFENCE);
    }

    public function fetchResourceBonus(): float
    {
        return $this->getPercentageForPassive(PassiveSkillTypeValue::KINGDOM_RESOURCE_GAIN);
    }

    public function fetchUnitCostReduction(): float
    {
        return $this->getPercentageForPassive(PassiveSkillTypeValue::KINGDOM_UNIT_COST_REDUCTION);
    }

    public function fetchBuildingCostReduction(): float
    {
        return $this->getPercentageForPassive(PassiveSkillTypeValue::KINGDOM_BUILDING_COST_REDUCTION);
    }

    public function fetchIronCostReduction(): float
    {
        return $this->getPercentageForPassive(PassiveSkillTypeValue::IRON_COST_REDUCTION);
    }

    public function fetchSmeltingTimeReduction(): float
    {
        return $this->getPercentageForPassive(PassiveSkillTypeValue::STEEL_SMELTING_TIME_REDUCTION);
    }

    public function fetchPopulationCostReduction(): float
    {
        return $this->getPercentageForPassive(PassiveSkillTypeValue::POPULATION_COST_REDUCTION);
    }

    public function fetchAirShipAttackIncrease(): float
    {
        return $this->getPercentageForPassive(PassiveSkillTypeValue::AIRSHIP_ATTACK_INCREASE);
    }

    public function fetchAirShipDefenceIncrease(): float
    {
        return $this->getPercentageForPassive(PassiveSkillTypeValue::AIRSHIP_UNIT_DEFENCE);
    }

    public function fetchKingBasedSkillValue(string $attribute): float
    {
        return $this->character->skills->filter(function ($skill) {
            return $skill->skill_type === SkillTypeValue::EFFECTS_KINGDOM->value;
        })->first()->{$attribute};
    }

    public function fetchKingdomDefenceBonus(): float
    {
        $passiveBonus = $this->fetchDefenceBonusFromPassive();
        $treasury = $this->fetchTreasuryDefenceBonus();
        $walls = $this->getWallsDefence();
        $goldBars = $this->fetchGoldBarsDefenceBonus();

        return $walls + $treasury + $goldBars + $passiveBonus;
    }

    public function fetchTreasuryDefenceBonus(): float
    {
        return $this->treasury / KingdomMaxValue::MAX_TREASURY;
    }

    public function fetchGoldBarsDefenceBonus(): float
    {
        return $this->gold_bars / KingdomMaxValue::MAX_GOLD_BARS;
    }

    public function kingdomItemResistanceBonus(): float
    {
        if (is_null($this->character)) {
            return 0;
        }

        $loyalty = $this->character->factionLoyalties()->where('is_pledged', true)->first();

        if (is_null($loyalty)) {
            return 0;
        }

        $factionNpcs = $loyalty->factionLoyaltyNpcs;

        $totalDefence = $factionNpcs->sum('current_kingdom_item_defence_bonus');

        return min($totalDefence, .95);
    }

    public function getWallsDefence(): float
    {
        $walls = $this->buildings->filter(function ($building) {
            return $building->gameBuilding->is_walls;
        })->first();

        if (is_null($walls)) {
            return 0.0;
        }

        if ($walls->current_durability <= 0) {
            return 0.0;
        }

        if ($walls->current_durability < $walls->max_durability) {
            $wallDefenceReduction = 1.0 - ($walls->current_durability / $walls->max_durability);
            $baseDefence = ($walls->level / $walls->gameBuilding->max_level) * 100;
            $totalDefence = ($baseDefence - $baseDefence * $wallDefenceReduction) / 100;

            if ($totalDefence < 0) {
                return 0;
            }

            return $totalDefence;
        }

        return $walls->level / $walls->gameBuilding->max_level;
    }

    public function gameMap()
    {
        return $this->belongsTo(GameMap::class, 'game_map_id', 'id');
    }

    public function buildings()
    {
        return $this->hasMany(KingdomBuilding::class, 'kingdom_id', 'id');
    }

    public function buildingsQueue()
    {
        return $this->hasMany(BuildingInQueue::class, 'kingdom_id', 'id');
    }

    public function capitalCityBuildingQueue()
    {
        return $this->hasMany(CapitalCityBuildingQueue::class, 'kingdom_id', 'id');
    }

    public function unitsQueue()
    {
        return $this->hasMany(CapitalCityUnitQueue::class, 'kingdom_id', 'id');
    }

    public function unitsMovementQueue()
    {
        return $this->hasMany(UnitMovementQueue::class, 'from_kingdom_id', 'id');
    }

    public function character()
    {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }

    public function units()
    {
        return $this->hasMany(KingdomUnit::class, 'kingdom_id', 'id');
    }

    protected static function newFactory()
    {
        return KingdomFactory::new();
    }

    protected function getPercentageForPassive(int $passiveType): float
    {
        $character = $this->character;

        if (is_null($character)) {
            return 0.0;
        }

        $passive = $character->passiveSkills->filter(function ($passiveSkill) use ($passiveType) {
            return $passiveSkill->passiveSkill->effect_type === $passiveType;
        })->first();

        return $passive->current_level * $passive->passiveSkill->bonus_per_level;
    }
}
