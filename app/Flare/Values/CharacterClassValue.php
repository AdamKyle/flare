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
    const GUNSLINGER       = 'Gunslinger';
    const DANCER           = 'Dancer';

    // These are special classes that require you to unlock them
    // via the class rank's system:

    const PRISONER         = 'Prisoner';
    const ALCOHOLIC        = 'Alcoholic';
    const MERCHANT         = 'Merchant';
    const CLERIC           = 'Cleric';
    const BOOK_BINDER      = 'Book Binder';


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
        self::PRISONER         => 'Prisoner',
        self::ALCOHOLIC        => 'Alcoholic',
        self::MERCHANT         => 'Merchant',
        self::GUNSLINGER       => 'Gunslinger',
        self::DANCER           => 'Dancer',
        self::CLERIC           => 'Cleric',
        self::BOOK_BINDER      => 'Book Binder',
    ];

    protected $nonCaster = [
        self::FIGHTER,
        self::BLACKSMITH,
        self::RANGER,
        self::THIEF,
        self::VAMPIRE,
        self::PRISONER,
        self::ALCOHOLIC,
        self::MERCHANT,
        self::GUNSLINGER,
    ];

    protected $caster = [
        self::PROPHET,
        self::HERETIC,
        self::ARCANE_ALCHEMIST,
        self::BOOK_BINDER,
        self::CLERIC
    ];

    protected $healers = [
        self::PROPHET,
        self::RANGER,
        self::CLERIC
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

    public function getName(): string {
        return self::$values[$this->value];
    }

    /**
     * @return string[]
     */
    public static function getClasses(): array {
        return self::$values;
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

    /**
     * Is a prisoner?
     *
     * @return bool
     */
    public function isPrisoner(): bool {
        return $this->value === self::PRISONER;
    }

    /**
     * Is a alcoholic?
     *
     * @return bool
     */
    public function isAlcoholic(): bool {
        return $this->value === self::ALCOHOLIC;
    }

    /**
     * Is a gunslinger?
     *
     * @return bool
     */
    public function isGunslinger(): bool {
        return $this->value === self::GUNSLINGER;
    }

    /**
     * Is a dancer?
     *
     * @return bool
     */
    public function isDancer(): bool {
        return $this->value === self::DANCER;
    }

    /**
     * Is a book binder?
     *
     * @return bool
     */
    public function isBookBinder(): bool {
        return $this->value === self::BOOK_BINDER;
    }

    /**
     * Is a cleric?
     *
     * @return bool
     */
    public function isCleric(): bool {
        return $this->value === self::CLERIC;
    }

    /**
     * is a Merchant?
     *
     * @return bool
     */
    public function isMerchant(): bool {
        return $this->value === self::MERCHANT;
    }

    /**
     * Are we a caster class?
     *
     * @return bool
     */
    public function isCaster(): bool {
        return in_array($this->value, $this->caster);
    }

    /**
     * Are we a non caster class?
     *
     * @return bool
     */
    public function isNonCaster(): bool {
        return in_array($this->value, $this->nonCaster);
    }

    /**
     * Are a healer?
     *
     * @return bool
     */
    public function isHealer(): bool {
        return in_array($this->value, $this->healers);
    }

}
