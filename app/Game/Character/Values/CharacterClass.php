<?php

namespace App\Game\Character\Values;

enum CharacterClass: string
{
    case FIGHTER = 'Fighter';
    case HERETIC = 'Heretic';
    case PROPHET = 'Prophet';
    case RANGER = 'Ranger';
    case VAMPIRE = 'Vampire';
    case THIEF = 'Thief';
    case BLACKSMITH = 'Blacksmith';
    case ARCANE_ALCHEMIST = 'Arcane Alchemist';
    case GUNSLINGER = 'Gunslinger';
    case DANCER = 'Dancer';
    case BUCCANEER = 'Buccaneer';
    case PRISONER = 'Prisoner';
    case ALCOHOLIC = 'Alcoholic';
    case MERCHANT = 'Merchant';
    case CLERIC = 'Cleric';
    case BOOK_BINDER = 'Book Binder';
    case APOTHECARY = 'Apothecary';
    case BEASTMASTER = 'Beastmaster';

    public function getName(): string
    {
        return $this->value;
    }

    public static function getClasses(): array
    {
        return array_column(self::cases(), 'value', 'value');
    }

    public function isFighter(): bool
    {
        return $this === self::FIGHTER;
    }

    public function isHeretic(): bool
    {
        return $this === self::HERETIC;
    }

    public function isProphet(): bool
    {
        return $this === self::PROPHET;
    }

    public function isRanger(): bool
    {
        return $this === self::RANGER;
    }

    public function isVampire(): bool
    {
        return $this === self::VAMPIRE;
    }

    public function isThief(): bool
    {
        return $this === self::THIEF;
    }

    public function isBlacksmith(): bool
    {
        return $this === self::BLACKSMITH;
    }

    public function isArcaneAlchemist(): bool
    {
        return $this === self::ARCANE_ALCHEMIST;
    }

    public function isPrisoner(): bool
    {
        return $this === self::PRISONER;
    }

    public function isAlcoholic(): bool
    {
        return $this === self::ALCOHOLIC;
    }

    public function isGunslinger(): bool
    {
        return $this === self::GUNSLINGER;
    }

    public function isDancer(): bool
    {
        return $this === self::DANCER;
    }

    public function isBookBinder(): bool
    {
        return $this === self::BOOK_BINDER;
    }

    public function isCleric(): bool
    {
        return $this === self::CLERIC;
    }

    public function isMerchant(): bool
    {
        return $this === self::MERCHANT;
    }

    public function isApothecary(): bool
    {
        return $this === self::APOTHECARY;
    }

    public function isBuccaneer(): bool
    {
        return $this === self::BUCCANEER;
    }

    public function isBeastmaster(): bool
    {
        return $this === self::BEASTMASTER;
    }

    public function isCaster(): bool
    {
        return in_array($this, [self::PROPHET, self::HERETIC, self::ARCANE_ALCHEMIST, self::BOOK_BINDER, self::CLERIC, self::APOTHECARY], true);
    }

    public function isNonCaster(): bool
    {
        return in_array($this, [self::FIGHTER, self::BLACKSMITH, self::RANGER, self::THIEF, self::VAMPIRE, self::PRISONER, self::ALCOHOLIC, self::MERCHANT, self::GUNSLINGER, self::BUCCANEER, self::BEASTMASTER], true);
    }

    public function isHealer(): bool
    {
        return in_array($this, [self::PROPHET, self::RANGER, self::CLERIC, self::APOTHECARY], true);
    }
}
