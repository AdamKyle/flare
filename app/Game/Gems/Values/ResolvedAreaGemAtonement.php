<?php

namespace App\Game\Gems\Values;

/**
 * Immutable resolved Gem elemental atonement for a single Area Gem context.
 * The elemental type is expressed through the existing `GemTypeValue`
 * integer constants, consistent with the persisted Gem/Monster atonement
 * columns.
 */
class ResolvedAreaGemAtonement
{
    /**
     * @param int|null $type The resolved atonement elemental type, using the `GemTypeValue` constants.
     * @param float|null $amount The resolved atonement amount.
     */
    public function __construct(
        private readonly ?int $type,
        private readonly ?float $amount,
    ) {}

    /**
     * Build a no-effect resolved atonement result.
     */
    public static function none(): self
    {
        return new self(null, null);
    }

    /**
     * The resolved atonement elemental type, using the `GemTypeValue` constants.
     */
    public function type(): ?int
    {
        return $this->type;
    }

    /**
     * The resolved atonement amount.
     */
    public function amount(): ?float
    {
        return $this->amount;
    }

    /**
     * Determine whether this result carries an effective, positive atonement.
     */
    public function hasEffect(): bool
    {
        if (is_null($this->type) || is_null($this->amount)) {
            return false;
        }

        return $this->amount > 0.0;
    }

    /**
     * Serialize this atonement into the legacy/cache compatible field shape.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'atonement_type' => $this->type,
            'atonement_amount' => $this->amount,
        ];
    }
}
