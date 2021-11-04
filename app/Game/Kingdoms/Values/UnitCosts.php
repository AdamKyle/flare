<?php


namespace App\Game\Kingdoms\Values;


class UnitCosts
{

    const SPEARMEN  = 'Spearmen';
    const ARCHER    = 'Archer';
    const SWORDSMEN = 'Swordsmen';
    const RAM       = 'Ram';
    const TREBUCHET = 'Trebuchet';
    const PRIEST    = 'Priest';
    const CLERIC    = 'Cleric';
    const PALIDIN   = 'Paladin';
    const SETTLER   = 'Settler';
    const PERSON    = 'Person';

    protected static $values = [
        self::SPEARMEN   => self::SPEARMEN,
        self::ARCHER     => self::ARCHER,
        self::SWORDSMEN  => self::SWORDSMEN,
        self::RAM        => self::RAM,
        self::TREBUCHET  => self::TREBUCHET,
        self::PRIEST     => self::PRIEST,
        self::CLERIC     => self::CLERIC,
        self::PALIDIN    => self::PALIDIN,
        self::SETTLER    => self::SETTLER,
        self::PERSON     => self::PERSON,
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
                return 100;
            case self::PRIEST:
                return 75;
            case self::CLERIC:
                return 125;
            case self::PALIDIN:
                return 250;
            case self::SETTLER:
                return 1000;
            case self::PERSON:
                return 5;
            default:
                return 0;
        }
    }

    public function isSpearmen(): bool {
        return $ths->name === self::SPEARMEN;
    }

    public function isArcher(): bool {
        return $ths->name === self::ARCHER;
    }

    public function isSwordsmen(): bool {
        return $ths->name === self::SWORDSMEN;
    }

    public function isRam(): bool {
        return $ths->name === self::RAM;
    }

    public function isTrebuchet(): bool {
        return $ths->name === self::TREBUCHET;
    }

    public function isPriest(): bool {
        return $ths->name === self::PRIEST;
    }

    public function isCleric(): bool {
        return $ths->name === self::CLERIC;
    }

    public function isPaladin(): bool {
        return $ths->name === self::PALIDIN;
    }

    public function isSettler(): bool {
        return $ths->name === self::SETTLER;
    }

    public function isPerson(): bool {
        return $this->name === self::PERSON;
    }
}
