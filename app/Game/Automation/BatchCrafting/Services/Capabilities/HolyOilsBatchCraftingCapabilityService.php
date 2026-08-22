<?php

namespace App\Game\Automation\BatchCrafting\Services\Capabilities;

use App\Flare\Models\Character;
use App\Game\Npcs\Actions\WorkBench\Services\HolyItemService;

class HolyOilsBatchCraftingCapabilityService
{
    public function __construct(private readonly HolyItemService $holyItemService) {}

    /**
     * Build the complete Holy Oils capability facts for the character.
     *
     * @param  Character  $character  The character requesting capability facts.
     * @return array The Holy Oils capability facts payload.
     */
    public function build(Character $character): array
    {
        $hasEligibleTarget = $this->holyItemService->hasEligibleInventoryTarget($character)
            || $this->holyItemService->hasEligibleInventorySetTarget($character);

        return [
            'can_holy_oils' => $hasEligibleTarget && $this->holyItemService->hasEligibleHolyOil($character),
        ];
    }
}
