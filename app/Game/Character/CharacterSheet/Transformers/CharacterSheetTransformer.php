<?php

namespace App\Game\Character\CharacterSheet\Transformers;

use App\Flare\Models\Character;
use App\Flare\Transformers\BaseTransformer;
use App\Game\Character\CharacterInventory\Services\CharacterActiveBoonService;
use App\Game\Character\CharacterInventory\Transformers\CharacterInventoryCountTransformer;

class CharacterSheetTransformer extends BaseTransformer
{
    public function __construct(
        private readonly CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer,
        private readonly CharacterBaseDetailsTransformer $characterBaseDetailsTransformer,
        private readonly CharacterCurrenciesTransformer $characterCurrenciesTransformer,
        private readonly CharacterResistanceInfoTransformer $characterResistanceInfoTransformer,
        private readonly CharacterElementalAtonementTransformer $characterElementalAtonementTransformer,
        private readonly CharacterReincarnationInfoTransformer $characterReincarnationInfoTransformer,
        private readonly CharacterInventoryCountTransformer $characterInventoryCountTransformer,
        private readonly CharacterActiveBoonService $characterActiveBoonService,
    ) {}

    /**
     * Gets the complete initial response data for the character sheet.
     */
    public function transform(Character $character): array
    {
        $baseInfo = $this->characterSheetBaseInfoTransformer->transform($character);
        $baseDetails = $this->characterBaseDetailsTransformer->transform($character);
        $currencies = $this->characterCurrenciesTransformer->transform($character);

        return array_merge($baseInfo, $baseDetails, $currencies, [
            'inventory_count' => $this->characterInventoryCountTransformer->transform($character),
            'resistance_info' => ['data' => $this->characterResistanceInfoTransformer->transform($character)],
            'elemental_atonements' => $this->characterElementalAtonementTransformer->transform($character),
            'reincarnation_info' => ['data' => $this->characterReincarnationInfoTransformer->transform($character)],
            'active_boons' => $this->characterActiveBoonService->activeBoons($character),
        ]);
    }
}
