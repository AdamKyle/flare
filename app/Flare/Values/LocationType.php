<?php

namespace App\Flare\Values;

enum LocationType: int
{
    case PURGATORY_SMITH_HOUSE = 0;
    case GOLD_MINES = 1;
    case PURGATORY_DUNGEONS = 2;
    case UNDERWATER_CAVES = 3;
    case TEAR_FABRIC_TIME = 4;
    case THE_OLD_CHURCH = 5;
    case TWISTED_GATE = 6;
    case ALCHEMY_CHURCH = 7;
    case LORDS_STRONG_HOLD = 8;
    case BROKEN_ANVIL = 9;
    case TWISTED_MAIDENS_DUNGEONS = 10;
    case CAVE_OF_MEMORIES = 11;
    case THE_CELLAR = 12;
    case SPECIAL = 13;

    public function label(): string
    {
        return match ($this) {
            self::PURGATORY_SMITH_HOUSE => 'Purgatory Smiths House',
            self::GOLD_MINES => 'Gold Mines',
            self::PURGATORY_DUNGEONS => 'Purgatory Dungeons',
            self::UNDERWATER_CAVES => 'Underwater Caves',
            self::TEAR_FABRIC_TIME => 'Tear in the fabrice of time',
            self::THE_OLD_CHURCH => 'The Old Church',
            self::TWISTED_GATE => 'The Twisted Gate',
            self::ALCHEMY_CHURCH => 'Alchemy Church',
            self::LORDS_STRONG_HOLD => 'Lords Strong Hold',
            self::BROKEN_ANVIL => 'Hells Broken Anvil',
            self::TWISTED_MAIDENS_DUNGEONS => 'Twisted Maidens Dungeons',
            self::CAVE_OF_MEMORIES => 'Cave of Memories',
            self::THE_CELLAR => 'The Cellar',
            self::SPECIAL => 'Special',
        };
    }

    public static function getNamedValues(): array
    {
        $values = [];

        foreach (self::cases() as $locationType) {
            $values[$locationType->value] = $locationType->label();
        }

        return $values;
    }

    public static function values(): array
    {
        return array_map(
            fn (LocationType $locationType): int => $locationType->value,
            self::cases()
        );
    }

    public static function manualQuestDropValues(): array
    {
        return [
            self::PURGATORY_SMITH_HOUSE->value,
            self::GOLD_MINES->value,
            self::PURGATORY_DUNGEONS->value,
            self::UNDERWATER_CAVES->value,
            self::TEAR_FABRIC_TIME->value,
            self::THE_OLD_CHURCH->value,
            self::TWISTED_GATE->value,
            self::ALCHEMY_CHURCH->value,
            self::LORDS_STRONG_HOLD->value,
            self::BROKEN_ANVIL->value,
            self::TWISTED_MAIDENS_DUNGEONS->value,
            self::THE_CELLAR->value,
            self::SPECIAL->value,
        ];
    }

    public function canDropManualQuestItems(): bool
    {
        return in_array($this->value, self::manualQuestDropValues(), true);
    }

    public function isPurgatorySmithHouse(): bool
    {
        return $this === self::PURGATORY_SMITH_HOUSE;
    }

    public function isGoldMines(): bool
    {
        return $this === self::GOLD_MINES;
    }

    public function isPurgatoryDungeons(): bool
    {
        return $this === self::PURGATORY_DUNGEONS;
    }

    public function isUnderWaterCaves(): bool
    {
        return $this === self::UNDERWATER_CAVES;
    }

    public function isTheOldChurch(): bool
    {
        return $this === self::THE_OLD_CHURCH;
    }

    public function isTwistedGate(): bool
    {
        return $this === self::TWISTED_GATE;
    }

    public function isTheCellar(): bool
    {
        return $this === self::THE_CELLAR;
    }

    public function isAlchemyChurch(): bool
    {
        return $this === self::ALCHEMY_CHURCH;
    }

    public function isLordsStrongHold(): bool
    {
        return $this === self::LORDS_STRONG_HOLD;
    }

    public function isHellsBrokenAnvil(): bool
    {
        return $this === self::BROKEN_ANVIL;
    }

    public function isTwistedMaidensDungeons(): bool
    {
        return $this === self::TWISTED_MAIDENS_DUNGEONS;
    }

    public function isCaveOfMemories(): bool
    {
        return $this === self::CAVE_OF_MEMORIES;
    }

    public function isSpecial(): bool
    {
        return $this === self::SPECIAL;
    }
}
