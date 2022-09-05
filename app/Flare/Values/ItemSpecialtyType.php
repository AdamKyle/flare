<?php

namespace App\Flare\Values;

use Exception;

class ItemSpecialtyType {

    /**
     * @var string $value
     */
    private string $value;

    const HELL_FORGED      = 'Hell Forged';
    const PURGATORY_CHAINS = 'Purgatory Chains';

    /**
     * @var string[] $values
     */
    protected static $values = [
        self::HELL_FORGED      => 'Hell Forged',
        self::PURGATORY_CHAINS => 'Purgatory Chains',
    ];

    /**
     * ItemEffectsValue constructor.
     *
     * Throws if the value does not exist in the array of const values.
     *
     * @param string $value
     * @throws Exception
     */
    public function __construct(string $value) {

        if (!in_array($value, self::$values)) {
            throw new Exception($value . ' does not exist.');
        }

        $this->value = $value;
    }
}
