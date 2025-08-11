<?php

namespace App\Game\Core\Services;

use App\Flare\Builders\BuildMythicItem;
use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Models\Map;
use App\Flare\Models\Monster;
use App\Flare\Values\CelestialType;
use App\Flare\Values\LocationType;
use App\Game\Battle\Services\BattleDrop;
use Exception;
use Facades\App\Flare\Calculators\DropCheckCalculator;

class DropCheckService
{
    private BattleDrop $battleDrop;

    private Monster $monster;

    private ?Location $locationWithEffect;

    private BuildMythicItem $buildMythicItem;

    private float $lootingChance = 0.0;

    private float $gameMapBonus = 0.0;

    public function __construct(BattleDrop $battleDrop, BuildMythicItem $buildMythicItem)
    {
        $this->battleDrop = $battleDrop;
        $this->buildMythicItem = $buildMythicItem;
    }

    /**
     * Process the drop check.
     *
     * @throws Exception
     */
    public function process(Character $character, Monster $monster): void
    {
        $this->lootingChance = $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;
        $this->monster = $monster;

        $gameMap = $character->map->gameMap;
        $characterMap = $character->map;

        if (! is_null($gameMap->drop_chance_bonus)) {
            $this->gameMapBonus = $gameMap->drop_chance_bonus;
        }

        $this->findLocationWithEffect($characterMap);

        $this->battleDrop = $this->battleDrop->setMonster($this->monster)
            ->setSpecialLocation($this->locationWithEffect)
            ->setGameMapBonus($this->gameMapBonus)
            ->setLootingChance($this->lootingChance);

        $this->handleDropChance($character);

        if ($monster->celestial_type === CelestialType::KING_CELESTIAL) {
            $this->handleMythicDrop($character, true);
        }

        if (! is_null($this->locationWithEffect)) {
            if (! is_null($this->locationWithEffect->type)) {
                $locationType = new LocationType($this->locationWithEffect->type);

                if ($locationType->isPurgatoryDungeons() && $character->currentAutomations->isEmpty()) {
                    $this->handleMythicDrop($character);
                }
            }
        }
    }

    /**
     * See if the player can have a mythic drop.
     *
     * @return void
     *
     * @throws Exception
     */
    public function handleMythicDrop(Character $character, bool $useLootingChance = false)
    {
        $canGetDrop = $this->canHaveMythic($useLootingChance);

        if ($canGetDrop) {
            $mythic = $this->buildMythicItem->fetchMythicItem($character);

            $this->battleDrop->giveMythicItem($character, $mythic);
        }
    }

    /**
     * Handles the drops themselves based on chance.
     *
     * @return void
     *
     * @throws Exception
     */
    public function handleDropChance(Character $character)
    {
        $canGetDrop = $this->canHaveDrop($character);

        $this->battleDrop->handleDrop($character, $canGetDrop);

        $this->battleDrop->handleMonsterQuestDrop($character);

        if (! is_null($this->locationWithEffect)) {
            $this->battleDrop->handleSpecialLocationQuestItem($character);
        }
    }

    /**
     * Are we at a location with an effect (special location)?
     */
    public function findLocationWithEffect(Map $map): void
    {
        $this->locationWithEffect = Location::whereNotNull('enemy_strength_increase')
            ->where('x', $map->character_position_x)
            ->where('y', $map->character_position_y)
            ->where('game_map_id', $map->game_map_id)
            ->first();
    }

    /**
     * Can we get the mythic item?
     */
    protected function canHaveMythic(bool $useLooting = false): bool
    {
        $chance = $this->lootingChance;

        if ($useLooting) {

            if ($chance > 0.15) {
                $chance = 0.15;
            }

            return DropCheckCalculator::fetchDifficultItemChance($chance);
        }

        return DropCheckCalculator::fetchDifficultItemChance();
    }

    /**
     * Can we have the drop?
     *
     * @throws Exception
     */
    protected function canHaveDrop(Character $character): bool
    {
        if (! is_null($this->locationWithEffect)) {

            $lootingBonus = $this->lootingChance;

            if ($lootingBonus > 0.45) {
                $lootingBonus = 0.45;
            }

            return DropCheckCalculator::fetchDifficultItemChance($lootingBonus, 100);
        }

        return DropCheckCalculator::fetchDropCheckChance($this->monster, $character->level, $this->lootingChance, $this->gameMapBonus);
    }
}
