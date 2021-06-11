<?php

namespace App\Flare\Values;

class NpcCommandTypes
{

    /**
     * @var string $value
     */
    private $value;

    const QUEST = 0;

    const TAKE_KINGDOM = 1;

    /**
     * @var string[] $values
     */
    protected static $values = [
        self::QUEST => 0,
        self::TAKE_KINGDOM => 1,
    ];

    protected static $namedValues = [
        0 => 'Quest',
        1 => 'Take Kingdom',
    ];

    /**
     * NpcTypes constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param string $value
     * @throws \Exception
     */
    public function __construct(string $value)
    {
        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Are we a quest?
     *
     * @return bool
     */
    public function isQuest(): bool {
        return $this->value = self::QUEST;
    }

    /**
     * Are we a take kingdom?
     *
     * @return bool
     */
    public function isTakeKingdom(): bool {
        return $this->value = self::TAKE_KINGDOM;
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
