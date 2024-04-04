<?php

namespace App\Game\CharacterInventory\Values;

use App\Flare\Values\ArmourTypes;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use Exception;

class EquippablePositions {

    /**
     * @var string $value
     */
    private string $value;


    const LEFT_HAND  = 'left-hand';
    const RIGHT_HAND = 'right-hand';
    const RING_ONE   = 'ring-one';
    const RING_TWO   = 'ring-two';
    const SPELL_ONE  = 'spell-one';
    const SPELL_TWO  = 'spell-two';
    const TRINKET    = 'trinket';
    const ARTIFACT   = 'artifact';

    // Armour positions
    const SLEEVES  = 'sleeves';
    const LEGGINGS = 'leggings';
    const GLOVES   = 'gloves';
    const SHIELD   = 'shield';
    const BODY     = 'body';
    const FEET     = 'feet';
    const HELMET   = 'helmet';


    /**
     * @var string[] $values
     */
    protected static array $values = [

        self::LEFT_HAND  => self::LEFT_HAND,
        self::RIGHT_HAND => self::RIGHT_HAND,
        self::RING_ONE   => self::RING_ONE,
        self::RING_TWO   => self::RING_TWO,
        self::SPELL_ONE  => self::SPELL_TWO,
        self::SPELL_TWO  => self::SPELL_TWO,
        self::TRINKET    => self::TRINKET,
        self::ARTIFACT   => self::ARTIFACT,
        self::SLEEVES    => self::SLEEVES,
        self::LEGGINGS   => self::LEGGINGS,
        self::GLOVES     => self::GLOVES,
        self::SHIELD     => self::SHIELD,
        self::BODY       => self::BODY,
        self::FEET       => self::FEET,
        self::HELMET     => self::HELMET,
    ];

    /**
     * @param string $value
     * @throws Exception
     */
    public function __construct(string $value) {
        if (!in_array($value, self::$values)) {
            throw new Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }

    public static function equippablePositions(): array {
        return [
            self::LEFT_HAND,
            self::RIGHT_HAND,
            self::RING_ONE,
            self::RING_TWO,
            self::SPELL_ONE,
            self::SPELL_TWO,
            self::TRINKET,
            self::ARTIFACT,
            self::SLEEVES,
            self::LEGGINGS,
            self::GLOVES,
            self::SHIELD,
            self::BODY,
            self::FEET,
            self::HELMET,
        ];
    }

    public static function typesForPositions(string $position): array {

        return match ($position) {
            self::LEFT_HAND,
            self::RIGHT_HAND => [
                WeaponTypes::WEAPON,
                WeaponTypes::STAVE,
                WeaponTypes::SCRATCH_AWL,
                WeaponTypes::MACE,
                WeaponTypes::GUN,
                WeaponTypes::BOW,
                WeaponTypes::FAN,
                WeaponTypes::HAMMER,
                ArmourTypes::SHIELD,
            ],
            self::RING_ONE,
            self::RING_TWO   => [WeaponTypes::RING],
            self::ARTIFACT   => [self::ARTIFACT],
            self::TRINKET    => [self::TRINKET],
            self::SPELL_ONE,
            self::SPELL_TWO  => [
                SpellTypes::HEALING,
                SpellTypes::DAMAGE,
            ],
            self::SLEEVES    => [self::SLEEVES],
            self::HELMET     => [self::HELMET],
            self::LEGGINGS   => [self::LEGGINGS],
            self::GLOVES     => [self::GLOVES],
            self::BODY       => [self::BODY],
            self::FEET       => [self::FEET],
            default          => [],
        };
    }

    public static function getOppisitePosition(string $position): ?string {
        return match ($position) {
            self::LEFT_HAND  => self::RIGHT_HAND,
            self::RIGHT_HAND => self::LEFT_HAND,
            self::RING_ONE   => self::RING_TWO,
            self::RING_TWO   => self::RING_ONE,
            self::SPELL_ONE  => self::SPELL_TWO,
            self::SPELL_TWO  => self::SPELL_ONE,
            default          => null
        };
    }
}
