<?php


namespace App\Game\Kingdoms\Values;


class UnitCosts
{

    const SPEARMEN        = 'Spearmen';
    const ARCHER          = 'Archer';
    const SWORDSMEN       = 'Swordsmen';
    const RAM             = 'Ram';
    const TREBUCHET       = 'Trebuchet';
    const MOUNTED_KNGHTS  = 'Mounted Knights';
    const MOUNTED_ARCHERS = 'Mounted Archers';
    const CANNON          = 'Cannon';
    const PRIEST          = 'Priest';
    const CLERIC          = 'Cleric';
    const PALIDIN         = 'Paladin';
    const SETTLER         = 'Settler';
    const PERSON          = 'Person';

    protected static $values = [
        self::SPEARMEN        => self::SPEARMEN,
        self::ARCHER          => self::ARCHER,
        self::SWORDSMEN       => self::SWORDSMEN,
        self::RAM             => self::RAM,
        self::TREBUCHET       => self::TREBUCHET,
        self::CANNON          => self::CANNON,
        self::MOUNTED_KNGHTS  => self::MOUNTED_KNGHTS,
        self::MOUNTED_ARCHERS => self::MOUNTED_ARCHERS,
        self::PRIEST          => self::PRIEST,
        self::CLERIC          => self::CLERIC,
        self::PALIDIN         => self::PALIDIN,
        self::SETTLER         => self::SETTLER,
        self::PERSON          => self::PERSON,
    ];

    private $name;

    public function __construct(string $name) {
        if (!in_array($name, self::$values)) {
            throw new \Exception($name . ' does not exist.');
        }

        $this->name = $name;
    }

    public function fetchCost(): int {

        switch($this->name) {
            case self::SPEARMEN:
                return 10;
            case self::ARCHER:
                return 50;
            case self::SWORDSMEN:
                return 100;
            case self::RAM:
                return 500;
            case self::TREBUCHET:
                return 1000;
            case self::CANNON:
                return 5000;
            case self::MOUNTED_KNGHTS:
                return 700;
            case self::MOUNTED_ARCHERS:
                return 300;
            case self::PRIEST:
                return 75;
            case self::CLERIC:
                return 125;
            case self::PALIDIN:
                return 250;
            case self::SETTLER:
                return 2500;
            case self::PERSON:
                return 5;
            default:
                return 0;
        }
    }

    public function isSpearmen(): bool {
        return $this->name === self::SPEARMEN;
    }

    public function isArcher(): bool {
        return $this->name === self::ARCHER;
    }

    public function isSwordsmen(): bool {
        return $this->name === self::SWORDSMEN;
    }

    public function isRam(): bool {
        return $this->name === self::RAM;
    }

    public function isTrebuchet(): bool {
        return $this->name === self::TREBUCHET;
    }

    public function isPriest(): bool {
        return $this->name === self::PRIEST;
    }

    public function isCleric(): bool {
        return $this->name === self::CLERIC;
    }

    public function isPaladin(): bool {
        return $this->name === self::PALIDIN;
    }

    public function isSettler(): bool {
        return $this->name === self::SETTLER;
    }

    public function isPerson(): bool {
        return $this->name === self::PERSON;
    }
}
