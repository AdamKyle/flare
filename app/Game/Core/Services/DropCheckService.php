<?php

namespace App\Game\Core\Services;

use App\Flare\Builders\BuildMythicItem;
use App\Flare\Values\CelestialType;
use App\Game\Battle\Services\BattleDrop;
use Exception;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use Facades\App\Flare\RandomNumber\RandomNumberGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Models\Map;
use App\Flare\Models\Monster;
use App\Flare\Values\LocationEffectValue;

class DropCheckService {

    /**
     * @var BattleDrop $battleDrop
     */
    private BattleDrop $battleDrop;

    /**
     * @var Monster $monster
     */
    private Monster $monster;

    /**
     * @var Location|null $locationWithEffect
     */
    private ?Location $locationWithEffect;

    /**
     * @var BuildMythicItem $buildMythicItem
     */
    private BuildMythicItem $buildMythicItem;

    /**
     * @var float $lootingChance
     */
    private float $lootingChance = 0.0;

    /**
     * @var float $gameMapBonus
     */
    private float $gameMapBonus = 0.0;


    /**
     * @param BattleDrop $battleDrop
     * @param BuildMythicItem $buildMythicItem
     */
    public function __construct(BattleDrop $battleDrop, BuildMythicItem $buildMythicItem) {
        $this->battleDrop      = $battleDrop;
        $this->buildMythicItem = $buildMythicItem;
    }

    /**
     * Process the drop check.
     *
     * @param Character $character
     * @param Monster $monster
     * @return void
     * @throws Exception
     */
    public function process(Character $character, Monster $monster): void {
        $this->lootingChance  = $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;
        $this->monster        = $monster;

        $gameMap              = $character->map->gameMap;
        $characterMap         = $character->map;

        if (!is_null($gameMap->drop_chance_bonus)) {
            $this->gameMapBonus = $gameMap->drop_chance_bonus;
        }

        $this->findLocationWithEffect($characterMap);

        $this->battleDrop = $this->battleDrop->setMonster($this->monster)
                                             ->setSpecialLocation($this->locationWithEffect)
                                             ->setGameMapBonus($this->gameMapBonus)
                                             ->setLootingChance($this->lootingChance);

        $this->handleDropChance($character);

        if ($monster->celestial_type === CelestialType::KING_CELESTIAL) {
            $this->handleMythicDrop($character);
        }
    }

    /**
     * See if the player can have a mythic drop.
     *
     * @param Character $character
     * @return void
     * @throws Exception
     */
    public function handleMythicDrop(Character $character) {
        $canGetDrop = $this->canHaveMythic();

        if ($canGetDrop) {
            $mythic = $this->buildMythicItem->fetchMythicItem($character);

            $this->battleDrop->giveMythicItem($character, $mythic);
        }
    }

    /**
     * Handles the drops themselves based on chance.
     *
     * @param Character $character
     * @return void
     * @throws Exception
     */
    public function handleDropChance(Character $character) {
        $canGetDrop = $this->canHaveDrop($character);

        $this->battleDrop->handleDrop($character, $canGetDrop);

        $this->battleDrop->handleMonsterQuestDrop($character);

        if (!is_null($this->locationWithEffect)) {
            $this->battleDrop->handleSpecialLocationQuestItem($character);
        }
    }

    /**
     * Are we at a location with an effect (special location)?
     *
     * @param Map $map
     * @return void
     */
    public function findLocationWithEffect(Map $map): void {
        $this->locationWithEffect = Location::whereNotNull('enemy_strength_type')
                                            ->where('x', $map->character_position_x)
                                            ->where('y', $map->character_position_y)
                                            ->where('game_map_id', $map->game_map_id)
                                            ->first();
    }

    /**
     * Can we get the mythic item?
     *
     * @return bool
     */
    protected function canHaveMythic(): bool {
        $chance = $this->lootingChance;

        if ($chance > 0.15) {
            $chance = 0.15;
        }

        $roll = RandomNumberGenerator::generateRandomNumber(1, 50 , 1, 100);
        $roll = $roll + $roll * $chance;

        return $roll > 99;
    }

    /**
     * Can we have the drop?
     *
     * @param Character $character
     * @return bool
     * @throws Exception
     */
    protected function canHaveDrop(Character $character): bool {
        if (!is_null($this->locationWithEffect)) {
            $dropRate   = new LocationEffectValue($this->locationWithEffect->enemy_strength_type);

            return DropCheckCalculator::fetchLocationDropChance($dropRate->fetchDropRate());
        }

        return DropCheckCalculator::fetchDropCheckChance($this->monster, $character->level, $this->lootingChance, $this->gameMapBonus);
    }
}
