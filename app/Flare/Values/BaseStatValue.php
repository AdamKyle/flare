<?php

namespace App\Flare\Values;

use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;

class BaseStatValue {

    /**
     * @var GameRace $race
     */
    private $race;

    /**
     * @var GameClass $class
     */
    private $class;

    /**
     * Set the race
     *
     * @param GameRace $race
     * @return BaseStatValue
     */
    public function setRace(GameRace $race): BaseStatValue {
        $this->race = $race;

        return $this;
    }

    /**
     * Set the class
     *
     * @param GameClass $class
     * @return BaseStatValue
     */
    public function setClass(GameClass $class): BaseStatValue {
        $this->class = $class;

        return $this;
    }

    /**
     * Get strength based on race and class modifiers.
     *
     * @return int
     */
    public function str(): int {
        $classMod = $this->class->str_mod > 0 ? $this->class->str_mod : 0;

        $modifier = $this->race->str_mod + $classMod;

        return round(10 + $modifier);
    }

    /**
     * Get dexterity based on race and class modifiers.
     *
     * @return int
     */
    public function dex(): int {
        $classMod = $this->class->dex_mod > 0 ? $this->class->dex_mod : 0;

        $modifier = $this->race->dex_mod + $classMod;

        return round(10 + $modifier);
    }

    /**
     * Get durability based on race and class modifiers.
     *
     * @return int
     */
    public function dur(): int {
        $classMod = $this->class->dur_mod > 0 ? $this->class->dur_mod : 0;

        $modifier = $this->race->dur_mod + $classMod;

        return round(10 + $modifier);
    }

    /**
     * Get durability based on race and class modifiers.
     *
     * @return int
     */
    public function chr(): int {
        $classMod = $this->class->chr_mod > 0 ? $this->class->chr_mod : 0;

        $modifier = $this->race->chr_mod + $classMod;

        return round(10 + $modifier);
    }

    /**
     * Get intelligence based on race and class modifiers.
     *
     * @return int
     */
    public function int(): int {
        $classMod = $this->class->int_mod > 0 ? $this->class->int_mod : 0;

        $modifier = $this->race->int_mod + $classMod;

        return round(10 + $modifier);
    }

    /**
     * Get Agility based on race and class modifiers.
     *
     * @return int
     */
    public function agi(): int {
        $classMod = $this->class->agi_mod > 0 ? $this->class->agi_mod : 0;

        $modifier = $this->race->agi_mod + $classMod;

        return round(10 + $modifier);
    }

    /**
     * Get Focus based on race and class modifiers.
     *
     * @return int
     */
    public function focus(): int {
        $classMod = $this->class->focus_mod > 0 ? $this->class->focus_mod : 0;

        $modifier = $this->race->focus_mod + $classMod;

        return round(10 + $modifier);
    }

    /**
     * Get ac based on race and class modifiers.
     *
     * This is done by taking 10 * modifier%
     *
     * @return int
     */
    public function ac(): int {
        $classMod = $this->class->defense_mod > 0 ? $this->class->defense_mod : 0;

        $modifier = $this->race->defense_mod + $classMod;

        return (10 + 10 * $modifier);
    }
}
