<?php

namespace App\Game\Kingdoms\Values;

class UnitNames
{
    const SPEARMEN = 'Spearmen';

    const ARCHER = 'Archer';

    const SWORDSMEN = 'Swordsmen';

    const RAM = 'Ram';

    const TREBUCHET = 'Trebuchet';

    const MOUNTED_KNIGHTS = 'Mounted Knights';

    const MOUNTED_ARCHERS = 'Mounted Archers';

    const CANNON = 'Cannon';

    const PRIEST = 'Priest';

    const CLERIC = 'Cleric';

    const PALADIN = 'Paladin';

    const SETTLER = 'Settler';

    const PERSON = 'Person';

    const AIRSHIP = 'Airship';

    public static $values = [
        self::SPEARMEN => self::SPEARMEN,
        self::ARCHER => self::ARCHER,
        self::SWORDSMEN => self::SWORDSMEN,
        self::RAM => self::RAM,
        self::TREBUCHET => self::TREBUCHET,
        self::CANNON => self::CANNON,
        self::MOUNTED_KNIGHTS => self::MOUNTED_KNIGHTS,
        self::MOUNTED_ARCHERS => self::MOUNTED_ARCHERS,
        self::PRIEST => self::PRIEST,
        self::CLERIC => self::CLERIC,
        self::PALADIN => self::PALADIN,
        self::SETTLER => self::SETTLER,
        self::PERSON => self::PERSON,
        self::AIRSHIP => self::AIRSHIP,
    ];

    private $name;

    public function __construct(string $name)
    {
        if (! in_array($name, self::$values)) {
            throw new \Exception($name.' does not exist.');
        }

        $this->name = $name;
    }

    public function isSpearmen(): bool
    {
        return $this->name === self::SPEARMEN;
    }

    public function isArcher(): bool
    {
        return $this->name === self::ARCHER;
    }

    public function isSwordsmen(): bool
    {
        return $this->name === self::SWORDSMEN;
    }

    public function isRam(): bool
    {
        return $this->name === self::RAM;
    }

    public function isTrebuchet(): bool
    {
        return $this->name === self::TREBUCHET;
    }

    public function isPriest(): bool
    {
        return $this->name === self::PRIEST;
    }

    public function isCleric(): bool
    {
        return $this->name === self::CLERIC;
    }

    public function isPaladin(): bool
    {
        return $this->name === self::PALADIN;
    }

    public function isSettler(): bool
    {
        return $this->name === self::SETTLER;
    }

    public function isPerson(): bool
    {
        return $this->name === self::PERSON;
    }

    public function isAirship(): bool
    {
        return $this->name === self::AIRSHIP;
    }
}
