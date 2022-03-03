<?php

namespace App\Flare\Values;

class CharacterClassValue {

    const FIGHTER          = 'Fighter';
    const HERETIC          = 'Heretic';
    const PROPHET          = 'Prophet';
    const RANGER           = 'Ranger';
    const VAMPIRE          = 'Vampire';
    const THIEF            = 'Thief';
    const BLACKSMITH       = 'Blacksmith';
    const ARCANE_ALCHEMIST = 'Arcane Alchemist';

    /**
     * @var string $value
     */
    private string $value;

    /**
     * @var string[] $values
     */
    protected static $values = [
        self::FIGHTER          => 'Fighter',
        self::HERETIC          => 'Heretic',
        self::PROPHET          => 'Prophet',
        self::RANGER           => 'Ranger',
        self::THIEF            => 'Thief',
        self::VAMPIRE          => 'Vampire',
        self::BLACKSMITH       => 'Blacksmith',
        self::ARCANE_ALCHEMIST => 'Arcane Alchemist',
    ];

    /**
     * CharacterClassValue constructor.
     *
     * @param string $value
     * @throws \Exception
     */
    public function __construct(string $value) {
        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value  = $value;
    }

    /**
     * Are we a fighter?
     *
     * @return bool
     */
    public function isFighter(): bool {
        return $this->value === self::FIGHTER;
    }

    /**
     * Are we a heretic?
     *
     * @return bool
     */
    public function isHeretic(): bool {
        return $this->value === self::HERETIC;
    }

    /**
     * Are we a prophet?
     *
     * @return bool
     */
    public function isProphet(): bool {
        return $this->value === self::PROPHET;
    }

    /**
     * Are we a ranger class?
     *
     * @return bool
     */
    public function isRanger(): bool {
        return $this->value === self::RANGER;
    }

    /**
     * Are we a vampire?
     *
     * @return bool
     */
    public function isVampire(): bool {
        return $this->value === self::VAMPIRE;
    }

    /**
     * Are we a thief?
     *
     * @return bool
     */
    public function isThief(): bool {
        return $this->value === self::THIEF;
    }

    /**
     * Are we a blacksmith?
     *
     * @return bool
     */
    public function isBlacksmith(): bool {
        return $this->value === self::BLACKSMITH;
    }

    /**
     * Are we an arcane alchemist?
     *
     * @return bool
     */
    public function isArcaneAlchemist(): bool {
        return $this->value === self::ARCANE_ALCHEMIST;
    }
}
