<?php

namespace App\Flare\Values;

use Exception;

class NpcTypes
{

    /**
     * @var string $value
     */
    private $value;

    const KINGDOM_HOLDER = 0;

    const SUMMONER = 1;

    /**
     * @var string[] $values
     */
    protected static $values = [
        self::KINGDOM_HOLDER => 0,
        self::SUMMONER => 1
    ];

    /**
     * @var string[] $namedValues
     */
    protected static $namedValues = [
        0 => 'Kingdom Holder',
        1 => 'Summoner'
    ];

    /**
     * KingdomLogStatusValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param int $value
     * @throws Exception
     */
    public function __construct(int $value)
    {
        if (!in_array($value, self::$values)) {
            throw new Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Get all the named values.
     *
     * @return string[]
     */
    public static function getNamedValues(): array {
        return self::$namedValues;
    }

    /**
     * See if the name exists in a named value.
     *
     * If it does return it, if not throw an exception.
     *
     * @return string
     * @throws Exception
     */
    public function getNamedValue(): string {
        if (isset(self::$namedValues[$this->value])) {
            return self::$namedValues[$this->value];
        }

        throw new Exception($this->value . ' does not exist for named value');
    }
}
