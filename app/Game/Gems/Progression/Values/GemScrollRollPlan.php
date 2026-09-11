<?php

namespace App\Game\Gems\Progression\Values;

class GemScrollRollPlan
{
    /**
     * @param bool $dropped
     * @param ?GemScrollType $scrollType
     * @param ?GemScrollCurrencyType $currencyType
     * @param ?float $bonus
     * @param ?int $durationMinutes
     * @param ?float $socketChance
     * @param ?float $preGemChance
     */
    public function __construct(
        private readonly bool $dropped,
        private readonly ?GemScrollType $scrollType = null,
        private readonly ?GemScrollCurrencyType $currencyType = null,
        private readonly ?float $bonus = null,
        private readonly ?int $durationMinutes = null,
        private readonly ?float $socketChance = null,
        private readonly ?float $preGemChance = null,
    ) {}

    /**
     * Build the no-drop outcome for a kill that did not roll a Gem Scroll.
     *
     * @return self
     */
    public static function noDrop(): self
    {
        return new self(false);
    }

    /**
     * Whether this kill rolled a Gem Scroll drop.
     *
     * @return bool
     */
    public function dropped(): bool
    {
        return $this->dropped;
    }

    /**
     * The rolled Gem Scroll family, or null when nothing dropped.
     *
     * @return ?GemScrollType
     */
    public function scrollType(): ?GemScrollType
    {
        return $this->scrollType;
    }

    /**
     * The rolled currency type for a Currency Scroll, or null when not applicable.
     *
     * @return ?GemScrollCurrencyType
     */
    public function currencyType(): ?GemScrollCurrencyType
    {
        return $this->currencyType;
    }

    /**
     * The rolled primary bonus ratio for the dropped Scroll, or null when nothing dropped.
     *
     * @return ?float
     */
    public function bonus(): ?float
    {
        return $this->bonus;
    }

    /**
     * The rolled duration in minutes for the dropped Scroll, or null when nothing dropped.
     *
     * @return ?int
     */
    public function durationMinutes(): ?int
    {
        return $this->durationMinutes;
    }

    /**
     * The rolled Item Scroll socket chance, or null when not applicable.
     *
     * @return ?float
     */
    public function socketChance(): ?float
    {
        return $this->socketChance;
    }

    /**
     * The rolled Item Scroll pre-gem chance, or null when not applicable.
     *
     * @return ?float
     */
    public function preGemChance(): ?float
    {
        return $this->preGemChance;
    }

    /**
     * Serialize this plan into a compact primitive array for checkpoint storage.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'dropped' => $this->dropped,
            'scroll_type' => $this->scrollType?->value,
            'currency_type' => $this->currencyType?->value,
            'bonus' => $this->bonus,
            'duration_minutes' => $this->durationMinutes,
            'socket_chance' => $this->socketChance,
            'pre_gem_chance' => $this->preGemChance,
        ];
    }

    /**
     * Hydrate this plan from its checkpoint-stored primitive array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        if (! ($data['dropped'] ?? false)) {
            return self::noDrop();
        }

        return new self(
            true,
            is_null($data['scroll_type'] ?? null) ? null : GemScrollType::from($data['scroll_type']),
            is_null($data['currency_type'] ?? null) ? null : GemScrollCurrencyType::from($data['currency_type']),
            $data['bonus'] ?? null,
            $data['duration_minutes'] ?? null,
            $data['socket_chance'] ?? null,
            $data['pre_gem_chance'] ?? null,
        );
    }
}
