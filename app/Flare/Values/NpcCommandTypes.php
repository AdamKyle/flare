<?php

namespace App\Flare\Values;

class NpcCommandTypes
{
    /**
     * @var string
     */
    private $value;

    const QUEST = 0;

    const TAKE_KINGDOM = 1;

    const CONJURE = 2;

    const RE_ROLL = 3;

    /**
     * @var string[]
     */
    protected static $values = [
        self::QUEST => 0,
        self::TAKE_KINGDOM => 1,
        self::CONJURE => 2,
        self::RE_ROLL => 3,
    ];

    protected static $namedValues = [
        0 => 'Quest',
        1 => 'Take Kingdom',
        2 => 'Conjure',
        3 => 'Re-Roll',
    ];

    /**
     * NpcTypes constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param  string  $value
     *
     * @throws \Exception
     */
    public function __construct(int $value)
    {
        if (! in_array($value, self::$values)) {
            throw new \Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Are we a quest?
     */
    public function isQuest(): bool
    {
        return $this->value === self::QUEST;
    }

    /**
     * Are we a take kingdom?
     */
    public function isTakeKingdom(): bool
    {
        return $this->value === self::TAKE_KINGDOM;
    }

    /**
     * Are we a conjure?
     */
    public function isConjure(): bool
    {
        return $this->value === self::CONJURE;
    }

    /**
     * Is this a re-roll?
     */
    public function isReRoll(): bool
    {
        return $this->value === self::RE_ROLL;
    }

    /**
     * Get all the named values.
     *
     * @return string[]
     */
    public static function getNamedValues(): array
    {
        return self::$namedValues;
    }

    /**
     * See if the name exists in a named value.
     *
     * If it does return it, if not throw an exception.
     */
    public function getNamedValue(): string
    {
        return self::$namedValues[$this->value];
    }
}
