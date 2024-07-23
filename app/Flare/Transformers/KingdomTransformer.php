<?php

namespace App\Flare\Transformers;

use App\Flare\Models\PassiveSkill;
use App\Flare\Models\Quest;
use App\Flare\Models\SmeltingProgress;
use App\Flare\Values\FeatureTypes;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use League\Fractal\Resource\Collection;
use League\Fractal\TransformerAbstract;
use App\Flare\Models\GameUnit;
use App\Flare\Models\UnitMovementQueue;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Values\BuildingActions;
use App\Game\Kingdoms\Values\KingdomMaxValue;

class KingdomTransformer extends TransformerAbstract {

    /**
     * @var string[]
     */
    protected array $defaultIncludes = [
        'buildings',
        'units',
        'unitsInMovement'
    ];

    /**
     * @param Kingdom $kingdom
     * @return array
     */
    public function transform(Kingdom $kingdom) {
        return [
            'id'                        => $kingdom->id,
            'character_id'              => $kingdom->character_id,
            'game_map_id'               => $kingdom->game_map_id,
            'game_map_name'             => $kingdom->gameMap->name,
            'name'                      => $kingdom->name,
            'color'                     => $kingdom->color,
            'max_stone'                 => $kingdom->max_stone,
            'max_wood'                  => $kingdom->max_wood,
            'max_clay'                  => $kingdom->max_clay,
            'max_iron'                  => $kingdom->max_iron,
            'max_steel'                 => $kingdom->max_steel,
            'current_steel'             => $kingdom->current_steel,
            'current_stone'             => $kingdom->current_stone,
            'current_wood'              => $kingdom->current_wood,
            'current_clay'              => $kingdom->current_clay,
            'current_iron'              => $kingdom->current_iron,
            'current_population'        => $kingdom->current_population,
            'max_population'            => $kingdom->max_population,
            'x_position'                => $kingdom->x_position,
            'y_position'                => $kingdom->y_position,
            'current_morale'            => $kingdom->current_morale,
            'max_morale'                => $kingdom->max_morale,
            'treasury'                  => $kingdom->treasury,
            'gold_bars'                 => $kingdom->gold_bars,
            'building_queue'            => $kingdom->buildingsQueue,
            'unit_queue'                => $kingdom->unitsQueue,
            'unit_movement'             => $kingdom->unitsMovementQueue,
            'treasury_defence'          => $kingdom->treasury / KingdomMaxValue::MAX_TREASURY,
            'current_units'             => $kingdom->units,
            'smelting_time_left'        => $this->getTimeLeftOnSmelting($kingdom),
            'smelting_completed_at'     => $this->smeltingCompletedAt($kingdom),
            'smelting_amount'           => $this->getAmountSmelting($kingdom),
            'passive_defence'           => $kingdom->fetchDefenceBonusFromPassive(),
            'unit_cost_reduction'       => $kingdom->fetchUnitCostReduction(),
            'building_cost_reduction'   => $kingdom->fetchBuildingCostReduction(),
            'iron_cost_reduction'       => $kingdom->fetchIronCostReduction(),
            'population_cost_reduction' => $kingdom->fetchPopulationCostReduction(),
            'smelting_time_reduction'   => $kingdom->fetchSmeltingTimeReduction(),
            'can_access_bank'           => $this->canAccessGoblinCoinBank($kingdom),
            'can_access_smelter'        => $this->canAccessSmelter($kingdom),
            'can_access_resource_request' => $this->canAccessResourceRequest($kingdom),
            'can_access_capital_city'   => $this->canAccessCapitalCity($kingdom),
            'walls_defence'             => $kingdom->getWallsDefence(),
            'gold_bars_defence'         => $kingdom->fetchGoldBarsDefenceBonus(),
            'defence_bonus'             => $kingdom->fetchKingdomDefenceBonus(),
            'item_resistance_bonus'     => $kingdom->kingdomItemResistanceBonus(),
            'unit_time_reduction'       => $this->fetchTimeReductionBonus($kingdom, 'unit_time_reduction'),
            'building_time_reduction'   => $this->fetchTimeReductionBonus($kingdom, 'building_time_reduction'),
            'is_protected'              => !is_null($kingdom->protected_until),
            'protected_days_left'       => !is_null($kingdom->protected_until) ? now()->diffInDays($kingdom->protected_until) : 0,
            'is_under_attack'           => $this->isKingdomUnderAttack($kingdom),
            'is_capital'                => $kingdom->is_capital,
            'auto_walked'               => $kingdom->auto_walked,
            'small_council_data'        => $this->getSmallCouncilData($kingdom),
        ];
    }

    /**
     * @param Kingdom $kingdom
     * @return Collection
     */
    public function includeBuildings(Kingdom $kingdom): Collection {
        $buildings = $kingdom->buildings;

        return $this->collection($buildings, new KingdomBuildingTransformer());
    }

    /**
     * @param Kingdom $kingdom
     * @return Collection
     */
    public function includeUnits(Kingdom $kingdom): Collection {
        $units = GameUnit::all();

        return $this->collection($units, new UnitTransformer());
    }

    /**
     * @param Kingdom $kingdom
     * @return Collection
     */
    public function includeUnitsInMovement(Kingdom $kingdom): Collection {

        $unitMovementQueues = UnitMovementQueue::where('character_id', $kingdom->character_id)
                                               ->get();

        return $this->collection($unitMovementQueues, new UnitMovementTransformer());
    }

    /**
     * get smelting time left.
     *
     * @param Kingdom $kingdom
     * @return int
     */
    protected function getTimeLeftOnSmelting(Kingdom $kingdom): int {
        $smeltingQueue = SmeltingProgress::where('kingdom_id', $kingdom->id)->first();

        if (is_null($smeltingQueue)) {
            return 0;
        }

        return $smeltingQueue->completed_at->diffInseconds(now());
    }

    /**
     * get smelting time left.
     *
     * @param Kingdom $kingdom
     * @return int
     */
    protected function smeltingCompletedAt(Kingdom $kingdom): ?string {
        $smeltingQueue = SmeltingProgress::where('kingdom_id', $kingdom->id)->first();

        if (is_null($smeltingQueue)) {
            return null;
        }

        return $smeltingQueue->completed_at->toIso8601String();
    }

    /**
     * Get amount currently smelting.
     *
     * @param Kingdom $kingdom
     * @return int
     */
    protected function getAmountSmelting(Kingdom $kingdom): int {
        $smeltingQueue = SmeltingProgress::where('kingdom_id', $kingdom->id)->first();

        if (is_null($smeltingQueue)) {
            return 0;
        }

        return $smeltingQueue->amount_to_smelt;
    }

    /**
     * Can we access the goblin bank?
     *
     * @param Kingdom $kingdom
     * @return bool
     */
    protected function canAccessGoblinCoinBank(Kingdom $kingdom): bool {
        $building = $kingdom->buildings->filter(function($building) {
            return $building->name === BuildingActions::GOBLIN_COIN_BANK;
        })->first();

        if (is_null($building)) {
            return false;
        }

        return !$building->is_locked && BuildingActions::canAccessGoblinBank($building);
    }

    /**
     * Can we access the smelter?
     *
     * @param Kingdom $kingdom
     * @return bool
     */
    protected function canAccessSmelter(Kingdom $kingdom): bool {
        $building = $kingdom->buildings->filter(function($building) {
            return $building->name === BuildingActions::BLACKSMITHS_FURNACE;
        })->first();

        if (is_null($building)) {
            return false;
        }

        return !$building->is_locked && BuildingActions::canAccessSmelter($building);
    }

    /**
     * Can Access Resource Request?
     *
     * @param Kingdom $kingdom
     * @return bool
     */
    protected function canAccessResourceRequest(Kingdom $kingdom): bool {
        $building = $kingdom->buildings->filter(function($building) {
            return $building->name === BuildingActions::MARKET_PLACE;
        })->first();

        if (is_null($building)) {
            return false;
        }

        return !$building->is_locked && BuildingActions::canAccessResourceTransferRequest($building);
    }

    protected function canAccessCapitalCity(Kingdom $kingdom): bool {
        $character = $kingdom->character;

        $completedQuest = $character->questsCompleted->whereNotNull('quest_id')->filter(function ($completedQuest) {
            return $completedQuest->quest->unlocks_feature === FeatureTypes::CAPITAL_CITIES;
        })->first();

        return !is_null($completedQuest);
    }

    /**
     * Fetch Time Reduction bonus for attribute.
     *
     * @param Kingdom $kingdom
     * @param string $timeReductionAttribute
     * @return float
     */
    protected function fetchTimeReductionBonus(Kingdom $kingdom, string $timeReductionAttribute): float {
        $character = $kingdom->character;

        if (is_null($character)) {
            return 0.0;
        }

        $skill = $character->skills->filter(function($skill) {
            return $skill->type()->effectsKingdom();
        })->first();

        return $skill->{$timeReductionAttribute};
    }

    protected function getSmallCouncilData(Kingdom $kingdom): array | null {
        $features = [];

        if (!$kingdom->is_capital) {
            return null;
        }

        $completedQuest = $kingdom->character->questsCompleted->filter(function($completedQuest)  {
            return $completedQuest->unlocks_feature === FeatureTypes::CAPITAL_CITY_GOLD_BARS;
        })->first();


        if (is_null($completedQuest)) {
            $quest = Quest::where('unlocks_feature', FeatureTypes::CAPITAL_CITY_GOLD_BARS)->first();

            if (is_null($quest)) {
                $features['capital_city_gold_bars'] = [
                    'can_use' => false,
                    'required_quest_name' => $quest->name,
                    'for_npc_name' => $quest->npc->real_name,
                    'on_plane' => $quest->npc->gameMap->name,
                ];
            }
        } else {
            $features['capital_city_gold_bars'] = [
                'can_use' => true,
                'required_quest_name' => $$completedQuest->quest->name,
                'for_npc_name' => $$completedQuest->quest->npc->real_name,
                'on_plane' => $$completedQuest->quest->npc->gameMap->name,
            ];
        }

        return $features;
    }

    /**
     * Is the kingdom under attack?
     *
     * @param Kingdom $kingdom
     * @return bool
     */
    protected function isKingdomUnderAttack(Kingdom $kingdom): bool {
        return UnitMovementQueue::where('to_kingdom_id', $kingdom->id)->where('is_attacking', true)->count() > 0;
    }
}
