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

    private ?Location $locationWithEffect = null;

    private ?string $cachedLocationWithEffectKey = null;

    private ?Location $cachedLocationWithEffect = null;

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
    public function process(Character $character, Monster $monster, ?float $lootingChance = null): void
    {
        $this->gameMapBonus = 0.0;

        $this->lootingChance = $lootingChance ?? $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;
        $this->monster = $monster;

        $characterMap = $character->map;
        $gameMap = $characterMap->gameMap;

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

        if (is_null($this->locationWithEffect)) {
            return;
        }

        if (is_null($this->locationWithEffect->type)) {
            return;
        }

        $locationType = new LocationType($this->locationWithEffect->type);

        if ($locationType->isPurgatoryDungeons() && $character->currentAutomations->isEmpty()) {
            $this->handleMythicDrop($character);
        }
    }

    /**
     * See if the player can have a mythic drop.
     *
     * @return void
     *
     * @throws Exception
     */
    private function handleMythicDrop(Character $character, bool $useLootingChance = false): void
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
     * @param Character $character
     * @return void
     *
     * @throws Exception
     */
    private function handleDropChance(Character $character): void
    {
        $canGetDrop = $this->canHaveDrop($character);

        $this->battleDrop->handleDrop($character, $canGetDrop);

        $this->battleDrop->handleMonsterQuestDrop($character);

        $this->battleDrop->handleDelveLocationQuestItems($character);

        if (! is_null($this->locationWithEffect)) {
            $this->battleDrop->handleSpecialLocationQuestItem($character);
        }
    }

    /**
     * Are we at a location with an effect (special location)?
     */
    private function findLocationWithEffect(Map $map): void
    {
        $cacheKey = $this->makeLocationWithEffectCacheKey($map);

        if ($this->cachedLocationWithEffectKey === $cacheKey) {
            $this->locationWithEffect = $this->cachedLocationWithEffect;

            return;
        }

        $this->locationWithEffect = Location::whereNotNull('enemy_strength_type')
            ->where('x', $map->character_position_x)
            ->where('y', $map->character_position_y)
            ->where('game_map_id', $map->game_map_id)
            ->first();

        $this->cachedLocationWithEffectKey = $cacheKey;
        $this->cachedLocationWithEffect = $this->locationWithEffect;
    }

    /**
     * Build a cache key for determining if we need to re-query the location effect.
     */
    private function makeLocationWithEffectCacheKey(Map $map): string
    {
        return $map->game_map_id . ':' . $map->character_position_x . ':' . $map->character_position_y;
    }

    /**
     * Can we get the mythic item?
     */
    private function canHaveMythic(bool $useLooting = false): bool
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
    private function canHaveDrop(Character $character): bool
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
