<?php

namespace App\Game\Core\Values;

class FactionType {

    /**
     * @var string $value
     */
    private $value;

    const MERCENARY        = 'Mercenary';
    const SOLDIER          = 'Soldier';
    const SAVIOUR          = 'Saviour';
    const LEGENDARY_SLAYER = 'Legendary Slayer';

    protected static $values = [
        'Mercenary'        => self::MERCENARY,
        'Soldier'          => self::SOLDIER,
        'Saviour'          => self::SAVIOUR,
        'Legendary Slayer' => self::LEGENDARY_SLAYER,
    ];

    /**
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

    public static function getTitle(int $currentLevel): ?string {
        switch ($currentLevel) {
            case 1:
                return self::MERCENARY;
            case 2:
                return self::SOLDIER;
            case 3:
                return self::SAVIOUR;
            case 4:
                return self::LEGENDARY_SLAYER;
            default:
                return null;
        }
    }

    public function isMercenary(): bool {
        return $this->value === self::MERCENARY;
    }

    public function isSoldier(): bool {
        return $this->value === self::SOLDIER;
    }

    public function isSaviour(): bool {
        return $this->value === self::SAVIOUR;
    }

    public function isLegendarySlayer(): bool {
        return $this->value === self::LEGENDARY_SLAYER;
    }
}
