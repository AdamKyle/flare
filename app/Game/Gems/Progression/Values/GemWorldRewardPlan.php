<?php

namespace App\Game\Gems\Progression\Values;

class GemWorldRewardPlan
{
    /**
     * @param array $kills
     */
    public function __construct(
        private readonly array $kills,
    ) {}

    /**
     * Every rolled kill reward plan in this request.
     *
     * @return array
     */
    public function kills(): array
    {
        return $this->kills;
    }

    /**
     * Serialize this plan into a compact primitive array for checkpoint storage.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'kills' => array_map(
                fn (GemWorldKillRewardPlan $kill): array => $kill->toArray(),
                $this->kills,
            ),
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
        return new self(array_map(
            fn (array $kill): GemWorldKillRewardPlan => GemWorldKillRewardPlan::fromArray($kill),
            $data['kills'] ?? [],
        ));
    }
}
