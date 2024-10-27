<?php

namespace App\Game\Events\Values;

use Exception;

class EventType
{
    const WEEKLY_CELESTIALS = 0;

    const WEEKLY_CURRENCY_DROPS = 1;

    const RAID_EVENT = 2;

    const WINTER_EVENT = 3;

    const PURGATORY_SMITH_HOUSE = 4;

    const GOLD_MINES = 5;

    const THE_OLD_CHURCH = 6;

    const DELUSIONAL_MEMORIES_EVENT = 7;

    const WEEKLY_FACTION_LOYALTY_EVENT = 8;

    const FEEDBACK_EVENT = 9;

    private int $value;

    /**
     * @var int[]
     */
    protected static array $values = [
        0 => self::WEEKLY_CELESTIALS,
        1 => self::WEEKLY_CURRENCY_DROPS,
        2 => self::RAID_EVENT,
        3 => self::WINTER_EVENT,
        4 => self::PURGATORY_SMITH_HOUSE,
        5 => self::GOLD_MINES,
        6 => self::THE_OLD_CHURCH,
        7 => self::DELUSIONAL_MEMORIES_EVENT,
        8 => self::WEEKLY_FACTION_LOYALTY_EVENT,
        9 => self::FEEDBACK_EVENT,
    ];

    protected static array $selection = [
        0 => 'Weekly Celestials',
        1 => 'Weekly Currency Drops',
        2 => 'Raid Event',
        3 => 'Winter Event',
        4 => 'Purgatory Smith House',
        5 => 'Gold Mines',
        6 => 'The Old Church',
        7 => 'Delusional Memories Event',
        8 => 'Weekly Faction Loyalty Event',
        9 => 'Tlessa\'s Feedback Event'
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @throws Exception
     */
    public function __construct(int $value)
    {
        if (! in_array($value, self::$values)) {
            throw new Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Return values for selection on the front end.
     */
    public static function getOptionsForSelect(): array
    {
        return self::$selection;
    }

    /**
     * Gets the name of the event.
     */
    public function getNameForEvent(): string
    {
        return self::$selection[$this->value];
    }

    /**
     * Is weekly celestials?
     */
    public function isWeeklyCelestials(): bool
    {
        return $this->value === self::WEEKLY_CELESTIALS;
    }

    /**
     * Is weekly currency drops?
     */
    public function isWeeklyCurrencyDrops(): bool
    {
        return $this->value === self::WEEKLY_CURRENCY_DROPS;
    }

    /**
     * Are we a raid event?
     */
    public function isRaidEvent(): bool
    {
        return $this->value === self::RAID_EVENT;
    }

    /**
     * Are we a winter event?
     */
    public function isWinterEvent(): bool
    {
        return $this->value === self::WINTER_EVENT;
    }

    /**
     * Is purgatory smith house event?
     */
    public function isPurgatorySmithHouseEvent(): bool
    {
        return $this->value === self::PURGATORY_SMITH_HOUSE;
    }

    /**
     * Is Gold Mines event?
     */
    public function isGoldMinesEvent(): bool
    {
        return $this->value === self::GOLD_MINES;
    }

    /**
     * Is The Old Church Event?
     */
    public function isTheOldChurchEvent(): bool
    {
        return $this->value === self::THE_OLD_CHURCH;
    }

    /**
     * Is Delusional Memories Event?
     */
    public function isDelusionalMemoriesEvent(): bool
    {
        return $this->value === self::DELUSIONAL_MEMORIES_EVENT;
    }

    /**
     * Is Delusional Memories Event?
     */
    public function isWeeklyFactionLoyaltyEvent(): bool
    {
        return $this->value === self::WEEKLY_FACTION_LOYALTY_EVENT;
    }

    /**
     * Are we a feedback based event?
     *
     * @return bool
     */
    public function isFeedbackEvent(): bool
    {
        return $this->value === self::FEEDBACK_EVENT;
    }
}
