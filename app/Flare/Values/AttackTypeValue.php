<?php

namespace App\Flare\Values;

class AttackTypeValue {

    /**
     * @var string $value
     */
    private $value;

    const ATTACK                 = 'attack';
    const VOIDED_ATTACK          = 'voided_attack';
    const CAST                   = 'cast';
    const VOIDED_CAST            = 'voided_cast';
    const CAST_AND_ATTACK        = 'cast_and_attack';
    const VOIDED_CAST_AND_ATTACK = 'voided_cast_and_attack';
    const ATTACK_AND_CAST        = 'attack_and_cast';
    const VOIDED_ATTACK_AND_CAST = 'voided_attack_and_cast';
    const DEFEND                 = 'defend';
    const VOIDED_DEFEND          = 'voided_defend';

    protected static $values = [
        'attack'                 => self::ATTACK,
        'voided_attack'          => self::VOIDED_ATTACK,
        'cast'                   => self::CAST,
        'voided_cast'            => self::VOIDED_CAST,
        'cast_and_attack'        => self::CAST_AND_ATTACK,
        'voided_cast_and_attack' => self::VOIDED_CAST_AND_ATTACK,
        'attack_and_cast'        => self::ATTACK_AND_CAST,
        'voided_attack_and_cast' => self::VOIDED_ATTACK_AND_CAST,
        'defend'                 => self::DEFEND,
        'voided_defend'          => self::VOIDED_DEFEND,
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

    public static function attackTypeExists(string $value): bool {
        return in_array($value, self::$values);
    }

    public function isAttack(): bool {
        return $this->value === self::ATTACK;
    }

    public function isVoidedAttack(): bool {
        return $this->value === self::VOIDED_ATTACK;
    }

    public function isCast(): bool {
        return $this->value === self::CAST;
    }

    public function isVoidedCast(): bool {
        return $this->value === self::VOIDED_CAST;
    }

    public function isAttackAndCast(): bool {
        return $this->value === self::ATTACK_AND_CAST;
    }

    public function isVoidedAttackAndCast(): bool {
        return $this->value === self::VOIDED_ATTACK_AND_CAST;
    }

    public function isCastAndAttack(): bool {
        return $this->value === self::CAST_AND_ATTACK;
    }

    public function isVoidedCastAndAttack(): bool {
        return $this->value === self::VOIDED_CAST_AND_ATTACK;
    }

    public function isDefend(): bool {
        return $this->value === self::DEFEND;
    }

    public function isVoidedDefend(): bool {
        return $this->value === self::VOIDED_DEFEND;
    }
}
