<?php

namespace App\Flare\Values;

use Exception;

class FeatureTypes {

    const REINCARNATION = 0;

    const COSMETIC_TEXT = 1;

    const NAME_TAGS = 2;

    /**
     * @var int $value
     */
    private int $value;

    /**
     * @var int[] $values
     */
    protected static array $values = [
        0 => self::REINCARNATION,
        1 => self::COSMETIC_TEXT,
        2 => self::NAME_TAGS,
    ];

    protected static array $valueNames = [
        self::REINCARNATION => 'Reincarnation',
        self::COSMETIC_TEXT => 'Cosmetic Text',
        self::NAME_TAGS     => 'Name Tags',
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

    public static function getSelectable(): array {
        return self::$valueNames;
    }

    public function getNameOfFeature(): string {
        return self::$valueNames[$this->value];
    }

    public function isReincarnation(): bool {
        return $this->value === self::REINCARNATION;
    }

    public function isCosmeticText(): bool {
        return $this->value === self::COSMETIC_TEXT;
    }

    public function isNameTag(): bool {
        return $this->value === self::NAME_TAGS;
    }
}
