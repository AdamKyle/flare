<?php

namespace App\Game\Events\Values;

use Exception;

class EventType {


    const WEEKLY_CELESTIALS           = 0;

    const MONTHLY_PVP                 = 1;

    const WEEKLY_CURRENCY_DROPS       = 2;

    const RAID_EVENT                  = 3;

    const WINTER_EVENT                = 4;

    const PURGATORY_SMITH_HOUSE       = 5;

    const GOLD_MINES                  = 6;

    const THE_OLD_CHURCH              = 7;

    const DELUSIONAL_MEMORIES_EVENT   = 8;

    /**
     * @var int $value
     */
    private int $value;

    /**
     * @var int[] $values
     */
    protected static array $values = [
        0 => self::WEEKLY_CELESTIALS,
        1 => self::MONTHLY_PVP,
        2 => self::WEEKLY_CURRENCY_DROPS,
        3 => self::RAID_EVENT,
        4 => self::WINTER_EVENT,
        5 => self::PURGATORY_SMITH_HOUSE,
        6 => self::GOLD_MINES,
        7 => self::THE_OLD_CHURCH,
        8 => self::DELUSIONAL_MEMORIES_EVENT,
    ];

    protected static array $selection = [
        0 => 'Weekly Celestials',
        1 => 'Monthly PVP',
        2 => 'Weekly Currency Drops',
        3 => 'Raid Event',
        4 => 'Winter Event',
        5 => 'Purgatory Smith House',
        6 => 'Gold Mines',
        7 => 'The Old Church',
        8 => 'Delusional Memories Event'
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @param int $value
     * @throws Exception
     */
    public function __construct(int $value) {
        if (!in_array($value, self::$values)) {
            throw new Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Return values for selection on the front end.
     *
     * @return array
     */
    public static function getOptionsForSelect(): array {
        return self::$selection;
    }

    /**
     * Gets the name of the event.
     *
     * @return string
     */
    public function getNameForEvent(): string {
        return self::$selection[$this->value];
    }

    /**
     * Is weekly celestials?
     *
     * @return bool
     */
    public function isWeeklyCelestials(): bool {
        return $this->value === self::WEEKLY_CELESTIALS;
    }

    /**
     * Is monthly pvp?
     *
     * @return bool
     */
    public function isMonthlyPVP(): bool {
        return $this->value === self::MONTHLY_PVP;
    }

    /**
     * Is weekly currency drops?
     *
     * @return bool
     */
    public function isWeeklyCurrencyDrops(): bool {
        return $this->value === self::WEEKLY_CURRENCY_DROPS;
    }

    /**
     * Are we a raid event?
     *
     * @return bool
     */
    public function isRaidEvent(): bool {
        return $this->value === self::RAID_EVENT;
    }

    /**
     * Are we a winter event?
     *
     * @return boolean
     */
    public function isWinterEvent(): bool {
        return $this->value === self::WINTER_EVENT;
    }

    /**
     * Is purgatory smith house event?
     *
     * @return bool
     */
    public function isPurgatorySmithHouseEvent(): bool {
        return $this->value === self::PURGATORY_SMITH_HOUSE;
    }

    /**
     * Is Gold Mines event?
     *
     * @return bool
     */
    public function isGoldMinesEvent(): bool {
        return $this->value === self::GOLD_MINES;
    }

    /**
     * Is The Old Church Event?
     *
     * @return bool
     */
    public function isTheOldChurchEvent(): bool {
        return $this->value === self::THE_OLD_CHURCH;
    }

    /**
     * Is Delusional Memories Event?
     *
     * @return bool
     */
    public function isDelusionalMemoriesEvent(): bool {
        return $this->value === self::DELUSIONAL_MEMORIES_EVENT;
    }
}
