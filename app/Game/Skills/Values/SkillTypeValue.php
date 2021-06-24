<?php

namespace App\Game\Skills\Values;

class SkillTypeValue {

    /**
     * @var string $value
     */
    private $value;

    const TRAINING   = 0;

    const CRAFTING   = 1;

    const ENCHANTING = 2;

    const DISENCHANTING = 3;

    /**
     * @var string[] $values
     */
    protected static $values = [
        self::TRAINING      => 0,
        self::CRAFTING      => 1,
        self::ENCHANTING    => 2,
        self::DISENCHANTING => 3,
    ];

    protected static $namedValues = [
        0 => 'Training',
        1 => 'Crafting',
        2 => 'Enchanting',
        3 => 'Disenchanting',
    ];

    /**
     * NpcTypes constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param string $value
     * @throws \Exception
     */
    public function __construct(int $value)
    {
        if (!in_array($value, self::$values)) {
            throw new \Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * is training.
     *
     * @return bool
     */
    public function isTraining(): bool {
        return $this->value === self::TRAINING;
    }

    /**
     * is crafting
     *
     * @return bool
     */
    public function isCrafting(): bool {
        return $this->value === self::CRAFTING;
    }

    /**
     * is enchanting
     *
     * @return bool
     */
    public function isEnchanting(): bool {
        return $this->value === self::ENCHANTING;
    }

    /**
     * is  disenchanting
     *
     * @return bool
     */
    public function isDisenchanting(): bool {
        return $this->value === self::DISENCHANTING;
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
