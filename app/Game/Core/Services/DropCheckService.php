<?php

namespace App\Game\Core\Services;

use App\Game\Battle\Services\BattleDrop;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Models\Map;
use App\Flare\Models\Monster;
use App\Flare\Values\LocationEffectValue;

class DropCheckService {

    /**
     * @var BattleDrop $battleDrop
     */
    private $battleDrop;

    /**
     * @var Monster $monster
     */
    private $monster;

    /**
     * @var Adventure $adventure
     */
    private $adventure;

    /**
     * @var Location $locationWithEffect
     */
    private $locationWithEffect;

    /**
     * @var float $lootingChance
     */
    private $lootingChance = 0.0;

    /**
     * @var float $gameMapBonus
     */
    private $gameMapBonus = 0.0;

    /**
     * @param BattleDrop $battleDrop
     */
    public function __construct(BattleDrop $battleDrop) {
        $this->battleDrop = $battleDrop;
    }

    /**
     * Process the drop check.
     *
     * @param Character $character
     * @param Monster $monster
     * @param Adventure|null $adventure
     * @return void
     */
    public function process(Character $character, Monster $monster, Adventure $adventure = null) {
        $this->lootingChance  = $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;
        $this->monster        = $monster;
        $this->adventure      = $adventure;

        $gameMap              = $character->map->gameMap;
        $characterMap         = $character->map;

        if (!is_null($gameMap->drop_chance_bonus)) {
            $this->gameMapBonus = $gameMap->drop_chance_bonus;
        }

        $this->findLocationWithEffect($characterMap);

        $this->battleDrop = $this->battleDrop->setMonster($this->monster)
                                             ->setSpecialLocation($this->locationWithEffect)
                                             ->setGameMapBonus($this->gameMapBonus)
                                             ->setAdventure($this->adventure )
                                             ->setLootingChance($this->lootingChance);

        $this->handleDropChance($character);
    }

    /**
     * Handles the drops themselves based on chance.
     *
     * @param Character $character
     * @return void
     */
    public function handleDropChance(Character $character) {
        $canGetDrop = $this->canHaveDrop();

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
    public function findLocationWithEffect(Map $map) {
        $this->locationWithEffect = Location::whereNotNull('enemy_strength_type')
                                            ->where('x', $map->character_position_x)
                                            ->where('y', $map->character_position_y)
                                            ->where('game_map_id', $map->game_map_id)
                                            ->first();
    }

    /**
     * Can we have the drop?
     *
     * @return bool
     * @throws \Exception
     */
    protected function canHaveDrop(): bool {
        if (!is_null($this->locationWithEffect)) {
            $dropRate   = new LocationEffectValue($this->locationWithEffect->enemy_strength_type);

            return DropCheckCalculator::fetchLocationDropChance($dropRate->fetchDropRate());
        }

        return DropCheckCalculator::fetchDropCheckChance($this->monster, $this->lootingChance, $this->gameMapBonus, $this->adventure);
    }
}