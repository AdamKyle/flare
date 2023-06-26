<?php

namespace App\Flare\AffixGenerator\Values;

use Exception;

class AffixGeneratorTypes {

    /**
     * @var string $value
     */
    private string $value;

    const STR_MOD                  = 'str_mod';
    const DEX_MOD                  = 'dex_mod';
    const CHR_MOD                  = 'chr_mod';
    const INT_MOD                  = 'int_mod';
    const AGI_MOD                  = 'agi_mod';
    const FOCUS_MOD                = 'focus_mod';
    const DUR_MOD                  = 'dur_mod';
    const STR_REDUCTION            = 'str_reduction';
    const DEX_REDUCTION            = 'dex_reduction';
    const CHR_REDUCTION            = 'chr_reductin';
    const INT_REDUCTION            = 'int_reduction';
    const AGI_REDUCTION            = 'agi_reduction';
    const FOCUS_REDUCTION          = 'focus_reduction';
    const DUR_REDUCTION            = 'dur_reduction';
    const CLASS_BONUS              = 'class_bonus';
    const BASE_DAMAGE_MOD          = 'base_damage_mod';
    const BASE_AC_MOD              = 'base_ac_mod';
    const BASE_HEALING_MOD         = 'base_healing_mod';
    const FIGHT_TIME_OUT_MOD_BONUS = 'fight_time_out_mod_bonus';
    const MOVE_TIME_OUT_MOD_BONUS  = 'move_time_out_mod_bonus';
    const DEVOURING_LIGHT          = 'devouring_light';
    const STEAL_LIFE_AMOUNT        = 'steal_light_amount';
    const DAMAGE                   = 'damage';
    const SKILL_REDUCTION          = 'skill_reduction';
    const RESISTANCE_REDUCTION     = 'resistance_reduction';

    /**
     * @var string[] $values
     */
    protected static array $values = [
        self::STR_MOD                  => self::STR_MOD,
        self::DEX_MOD                  => self::DEX_MOD,
        self::CHR_MOD                  => self::CHR_MOD,
        self::INT_MOD                  => self::INT_MOD,
        self::AGI_MOD                  => self::AGI_MOD,
        self::FOCUS_MOD                => self::FOCUS_MOD,
        self::DUR_MOD                  => self::DUR_MOD,
        self::STR_REDUCTION            => self::STR_REDUCTION,
        self::DEX_REDUCTION            => self::DEX_REDUCTION,
        self::CHR_REDUCTION            => self::CHR_REDUCTION,
        self::FOCUS_REDUCTION          => self::FOCUS_REDUCTION,
        self::INT_REDUCTION            => self::INT_REDUCTION,
        self::CHR_REDUCTION            => self::CHR_REDUCTION,
        self::AGI_REDUCTION            => self::AGI_REDUCTION,
        self::DUR_REDUCTION            => self::DUR_REDUCTION,
        self::CLASS_BONUS              => self::CLASS_BONUS,
        self::BASE_DAMAGE_MOD          => self::BASE_DAMAGE_MOD,
        self::BASE_AC_MOD              => self::BASE_AC_MOD,
        self::BASE_HEALING_MOD         => self::BASE_HEALING_MOD,
        self::FIGHT_TIME_OUT_MOD_BONUS => self::FIGHT_TIME_OUT_MOD_BONUS,
        self::MOVE_TIME_OUT_MOD_BONUS  => self::MOVE_TIME_OUT_MOD_BONUS,
        self::DEVOURING_LIGHT          => self::DEVOURING_LIGHT,
        self::STEAL_LIFE_AMOUNT        => self::STEAL_LIFE_AMOUNT,
        self::DAMAGE                   => self::DAMAGE,
        self::SKILL_REDUCTION          => self::SKILL_REDUCTION,
        self::RESISTANCE_REDUCTION     => self::RESISTANCE_REDUCTION,
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

    public static function getValuesForCommandSelection(): array {
        return array_values(self::$values);
    }

    public function isDamage(): bool {
        return $this->value === self::DAMAGE;
    }
}
