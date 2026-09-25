<?php

namespace App\Game\Character\CharacterSheet\Transformers;

use App\Flare\Models\Character;
use App\Flare\Transformers\BaseTransformer;
use App\Game\Character\CharacterInventory\Services\CharacterActiveBoonService;
use App\Game\Character\CharacterInventory\Transformers\CharacterInventoryCountTransformer;
use App\Game\Core\Currency\Services\CurrencyLimit;

class CharacterSheetTransformer extends BaseTransformer
{
    /**
     * @param CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer
     * @param CharacterBaseDetailsTransformer $characterBaseDetailsTransformer
     * @param CharacterCurrenciesTransformer $characterCurrenciesTransformer
     * @param CharacterResistanceInfoTransformer $characterResistanceInfoTransformer
     * @param CharacterElementalAtonementTransformer $characterElementalAtonementTransformer
     * @param CharacterReincarnationInfoTransformer $characterReincarnationInfoTransformer
     * @param CharacterInventoryCountTransformer $characterInventoryCountTransformer
     * @param CharacterActiveBoonService $characterActiveBoonService
     */
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
     * Build the complete initial character sheet payload, including the maximum amount of each currency.
     *
     * @param Character $character
     * @return array
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
            'currency_limits' => $this->currencyLimits(),
        ]);
    }

    /**
     * Return the maximum amount of each currency a character can hold.
     *
     * @return array
     */
    private function currencyLimits(): array
    {
        return [
            'gold' => CurrencyLimit::MAX_GOLD,
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ];
    }
}
