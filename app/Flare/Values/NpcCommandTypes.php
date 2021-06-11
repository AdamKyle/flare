<?php

namespace App\Flare\Values;

class NpcTypes
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
        self::TAKE_KINGOOM => 1,
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
}
