<?php

namespace App\Game\Gems\Progression\Values;

class GemSpecialItemRollPlan
{
    /**
     * @param GemItemRarity $candidateRarity
     * @param bool $succeeded
     * @param bool $socketed
     * @param ?int $socketCount
     * @param bool $preGemmed
     * @param ?int $gemCount
     */
    public function __construct(
        private readonly GemItemRarity $candidateRarity,
        private readonly bool $succeeded,
        private readonly bool $socketed = false,
        private readonly ?int $socketCount = null,
        private readonly bool $preGemmed = false,
        private readonly ?int $gemCount = null,
    ) {}

    /**
     * Build the failed outcome for a candidate rarity that did not pass its chance roll.
     *
     * @param GemItemRarity $candidateRarity
     * @return self
     */
    public static function failed(GemItemRarity $candidateRarity): self
    {
        return new self($candidateRarity, false);
    }

    /**
     * The candidate rarity that was rolled for this opportunity.
     *
     * @return GemItemRarity
     */
    public function candidateRarity(): GemItemRarity
    {
        return $this->candidateRarity;
    }

    /**
     * Whether the candidate rarity's chance roll succeeded.
     *
     * @return bool
     */
    public function succeeded(): bool
    {
        return $this->succeeded;
    }

    /**
     * Whether the delivered Item should be socketed.
     *
     * @return bool
     */
    public function socketed(): bool
    {
        return $this->socketed;
    }

    /**
     * The rolled socket count, or null when not socketed.
     *
     * @return ?int
     */
    public function socketCount(): ?int
    {
        return $this->socketCount;
    }

    /**
     * Whether the delivered Item's sockets should be pre-filled with Tier Four Gems.
     *
     * @return bool
     */
    public function preGemmed(): bool
    {
        return $this->preGemmed;
    }

    /**
     * The rolled pre-filled Gem count, or null when not pre-gemmed.
     *
     * @return ?int
     */
    public function gemCount(): ?int
    {
        return $this->gemCount;
    }

    /**
     * Serialize this plan into a compact primitive array for checkpoint storage.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'candidate_rarity' => $this->candidateRarity->value,
            'succeeded' => $this->succeeded,
            'socketed' => $this->socketed,
            'socket_count' => $this->socketCount,
            'pre_gemmed' => $this->preGemmed,
            'gem_count' => $this->gemCount,
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
        return new self(
            GemItemRarity::from($data['candidate_rarity']),
            $data['succeeded'] ?? false,
            $data['socketed'] ?? false,
            $data['socket_count'] ?? null,
            $data['pre_gemmed'] ?? false,
            $data['gem_count'] ?? null,
        );
    }
}
