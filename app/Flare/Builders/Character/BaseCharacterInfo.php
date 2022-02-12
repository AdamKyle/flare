<?php

namespace App\Flare\Builders\Character;

use App\Flare\Builders\Character\Traits\Boons;
use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Builders\Character\ClassDetails\ClassBonuses;
use App\Flare\Models\Character;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Map;
use App\Flare\Models\Skill;

class BaseCharacterInfo {

    use FetchEquipped, Boons;

    /**
     * @var ClassBonuses $classBonuses
     */
    private $classBonuses;

    /**
     * @param ClassBonuses $classBonuses
     */
    public function __construct(ClassBonuses $classBonuses) {
        $this->classBonuses = $classBonuses;
    }

    /**
     * Get the instance of the ClassBonus skill.
     *
     * @return ClassBonuses
     */
    public function getClassBonuses(): ClassBonuses {
        return $this->classBonuses;
    }

    /**
     * Fetch the character's modified stat.
     *
     * This is done based off:
     *
     * - Equipment
     * - Character boons
     * - Game Map (Hell or Purgatory can reduce stats)
     *
     * @param Character $character
     * @param string $stat
     * @return float
     */
    public function statMod(Character $character, string $stat): float {
        $base = $character->{$stat};

        $equipped = $this->fetchEquipped($character);

        if (is_null($equipped)) {
            return $this->applyCharacterBoons($character, $base);
        }

        foreach ($equipped as $slot) {
            $base += $base * $this->fetchModdedStat($stat, $slot->item);
        }

        $base  = $this->applyCharacterBoons($character, $base);
        $total = $this->applyCharacterBoons($character, $base, $stat . '_mod');
        $map   = $this->getGameMap($character);

        if ($map->mapType()->isHell() || $map->mapType()->isPurgatory()) {
            $total -= $total * $map->character_attack_reduction;
        }

        return $total;
    }

    /**
     * Build the character health.
     *
     * @param Character $character
     * @param bool $voided
     * @return int
     */
    public function buildHealth(Character $character, bool $voided = false): int {
        if ($character->is_dead) {
            return 0;
        }

        $baseHealth = $character->getInformation()->statMod('dur');

        $equipped   = $this->fetchEquipped($character);

        if ($voided || is_null($equipped)) {
            return $baseHealth;
        }

        $totalPercent = 0.0;

        foreach ($equipped as $slot) {
            if ($slot->equipped) {
                $percentage = $slot->item->getTotalPercentageForStat('dur');
                $totalPercent += $percentage;
            }
        }

        return $baseHealth + $baseHealth * $totalPercent;
    }

    /**
     * Build characters defence.
     *
     * @param Character $character
     * @param bool $voided
     * @return float
     * @throws \Exception
     */
    public function buildDefence(Character $character, bool $voided = false) {
        return round((10 + $this->getDefence($character, $voided)) * (1 + $this->fetchSkillACMod($character) + $this->classBonuses->getFightersDefence($character)));
    }

    /**
     * Gets a specific skill based on name.
     *
     * @param Character $character
     * @param string $skillName
     * @return float
     */
    public function getSkill(Character $character, string $skillName): float {
        $gameSkill = GameSkill::where('name', $skillName)->first();

        $skill = Skill::where('character_id', $character->id)->where('game_skill_id', $gameSkill->id)->first();

        if (is_null($skill)) {
            return 0.0;
        }

        return $skill->skill_bonus;
    }

    /**
     * Applies character boons to the stat
     *
     * @param Character $character
     * @param $base
     * @param string|null $statAttribute
     * @return float
     */
    protected function applyCharacterBoons(Character $character, $base, string $statAttribute = null): float {
        $boons = $this->fetchCharacterBoons($character);

        if (!is_null($statAttribute) && $boons->isNotEmpty()) {
            $bonus = $this->fetchStatIncrease($character, $statAttribute);

            $base = $base + $base * $bonus;
        } else if ($boons->isNotEmpty()) {
            $bonus = $this->fetchStatIncreaseFromType($character);

            $base = $base + $base * $bonus;
        }

        return $base;
    }

    /**
     * Fetches modded stat based on equipment affixes.
     *
     * @param string $stat
     * @param Item $item
     * @return float
     */
    protected function fetchModdedStat(string $stat, Item $item): float {
        $staMod          = $item->{$stat . '_mod'};
        $totalPercentage = !is_null($staMod) ? $staMod : 0.0;

        $itemPrefix = ItemAffix::where('id', $item->item_prefix_id)->first();
        $itemSuffix = ItemAffix::where('id', $item->item_suffix_id)->first();

        if (!is_null($itemPrefix)) {
            $prefixMod        = $itemPrefix->{$stat . '_mod'};
            $totalPercentage += !is_null($prefixMod) ? $prefixMod : 0.0;
        }

        if (!is_null($itemSuffix)) {
            $suffixMod        = $itemSuffix->{$stat . '_mod'};
            $totalPercentage += !is_null($suffixMod) ? $suffixMod : 0.0;
        }

        return  $totalPercentage;
    }

    /**
     * Get character defence based on inventory equipped.
     *
     * @param Character $character
     * @param bool $voided
     * @return int
     */
    protected function getDefence(Character $character, bool $voided = false): int {
        $defence = 0;

        $equipped = $this->fetchEquipped($character);

        if (is_null($equipped)) {
            return $defence;
        }

        foreach ($equipped as $slot) {
            if (!$voided) {
                $defence += $slot->item->getTotalDefence();
            } else {
                $defence += $slot->item->base_ac;
            }
        }

        if ($defence !== 10) {
            return $defence / 6;
        }

        return $defence;
    }

    /**
     * Fetch the percentage bonus to modify the AC by.
     *
     * @param Character $character
     * @return float
     */
    protected function fetchSkillACMod(Character $character): float {
        $percentageBonus = 0.0;

        $class      = GameClass::find($character->game_class_id);

        $gameSkills = GameSkill::where('game_class_id', $class->id)->get()->pluck('id')->toArray();

        $skills     = Skill::where('character_id', $character->id)->whereIn('game_skill_id', $gameSkills)->get();

        foreach ($skills as $skill) {
            $percentageBonus += $skill->base_ac_mod;
        }

        return $percentageBonus;
    }

    /**
     * Gets the character game map.
     *
     * @param Character $character
     * @return GameMap
     */
    protected function getGameMap(Character $character): GameMap {
        $map = Map::where('character_id', $character->id)->first();

        return GameMap::find($map->game_map_id);
    }

}
