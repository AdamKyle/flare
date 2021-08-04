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
        $modifier = $this->race->str_mod + $this->class->str_mod;

        return round(10 + $modifier);
    }

    /**
     * Get dexterity based on race and class modifiers.
     *
     * @return int
     */
    public function dex(): int {
        $modifier = $this->race->dex_mod + $this->class->dex_mod;

        return round(10 + $modifier);
    }

    /**
     * Get durabillity based on race and class modifiers.
     *
     * @return int
     */
    public function dur(): int {
        $modifier = $this->race->dur_mod + $this->class->dur_mod;

        return round(10 + $modifier);
    }

    /**
     * Get durabillity based on race and class modifiers.
     *
     * @return int
     */
    public function chr(): int {
        $modifier = $this->race->chr_mod + $this->class->chr_mod;

        return round(10 + $modifier);
    }

    /**
     * Get intelligence based on race and class modifiers.
     *
     * @return int
     */
    public function int(): int {
        $modifier = $this->race->int_mod + $this->class->int_mod;

        return round(10 + $modifier);
    }

    /**
     * Get Agility based on race and class modifiers.
     *
     * @return int
     */
    public function agi(): int {
        $modifier = $this->race->agi_mod + $this->class->agi_mod;

        return round(10 + $modifier);
    }

    /**
     * Get Focus based on race and class modifiers.
     *
     * @return int
     */
    public function focus(): int {
        $modifier = $this->race->agi_mod + $this->class->agi_mod;

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
        $modifier = $this->race->defense_mod + $this->class->defense_mod;

        return round(10 * ($modifier < 1 ? (1 + $modifier) : $modifier ));
    }
}
