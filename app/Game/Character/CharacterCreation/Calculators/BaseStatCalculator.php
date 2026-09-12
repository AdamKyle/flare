<?php

namespace App\Game\Character\CharacterCreation\Calculators;

use App\Flare\Models\GameClass;

class BaseStatCalculator
{
    /**
     * @var GameClass
     */
    private $class;

    /**
     * Set the Class used to calculate base stats.
     *
     * @param GameClass $class Class supplying base-stat modifiers.
     * @return BaseStatCalculator This calculator, configured with the supplied Class.
     */
    public function setClass(GameClass $class): BaseStatCalculator
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get strength based on the Class modifier.
     *
     * @return int Calculated base strength.
     */
    public function str(): int
    {
        $classMod = $this->class->str_mod > 0 ? $this->class->str_mod : 0;

        return round(10 + $classMod);
    }

    /**
     * Get dexterity based on the Class modifier.
     *
     * @return int Calculated base dexterity.
     */
    public function dex(): int
    {
        $classMod = $this->class->dex_mod > 0 ? $this->class->dex_mod : 0;

        return round(10 + $classMod);
    }

    /**
     * Get durability based on the Class modifier.
     *
     * @return int Calculated base durability.
     */
    public function dur(): int
    {
        $classMod = $this->class->dur_mod > 0 ? $this->class->dur_mod : 0;

        return round(10 + $classMod);
    }

    /**
     * Get charisma based on the Class modifier.
     *
     * @return int Calculated base charisma.
     */
    public function chr(): int
    {
        $classMod = $this->class->chr_mod > 0 ? $this->class->chr_mod : 0;

        return round(10 + $classMod);
    }

    /**
     * Get intelligence based on the Class modifier.
     *
     * @return int Calculated base intelligence.
     */
    public function int(): int
    {
        $classMod = $this->class->int_mod > 0 ? $this->class->int_mod : 0;

        return round(10 + $classMod);
    }

    /**
     * Get Agility based on the Class modifier.
     *
     * @return int Calculated base agility.
     */
    public function agi(): int
    {
        $classMod = $this->class->agi_mod > 0 ? $this->class->agi_mod : 0;

        return round(10 + $classMod);
    }

    /**
     * Get Focus based on the Class modifier.
     *
     * @return int Calculated base focus.
     */
    public function focus(): int
    {
        $classMod = $this->class->focus_mod > 0 ? $this->class->focus_mod : 0;

        return round(10 + $classMod);
    }

    /**
     * Get ac based on the Class defense modifier.
     *
     * This is done by taking 10 * modifier%
     *
     * @return int Calculated base armor class.
     */
    public function ac(): int
    {
        $classMod = $this->class->defense_mod > 0 ? $this->class->defense_mod : 0;

        return 10 + 10 * $classMod;
    }
}
