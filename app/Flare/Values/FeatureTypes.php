<?php

namespace App\Flare\Values;

use Exception;

class FeatureTypes {

    const MERCENARY = 0;

    const REINCARNATION = 1;

    const COSMETIC_TEXT = 2;

    const NAME_TAGS = 3;

    /**
     * @var int $value
     */
    private int $value;

    /**
     * @var int[] $values
     */
    protected static array $values = [
        0 => self::MERCENARY,
        1 => self::REINCARNATION,
        2 => self::COSMETIC_TEXT,
        3 => self::NAME_TAGS,
    ];

    protected static array $valueNames = [
        self::MERCENARY     => 'Mercenary',
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

    public function isMercenary(): bool {
        return $this->value === self::MERCENARY;
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
