<?php

namespace Tests\Setup\Items;

use App\Game\Core\Items\Enricher\ItemEnricherFactory;
use App\Game\Core\Items\Transformers\EquippableItemTransformer;
use App\Game\Core\Items\Transformers\ItemSocketTransformer;
use App\Game\Core\Items\Transformers\ItemTransformer;
use App\Game\Gems\Services\CharacterGemDetailsService;
use App\Game\Gems\Services\GemAbilityDuplicateService;

class ItemTransformerFactory
{
    /**
     * Build a real EquippableItemTransformer with its real Gem socket collaborators.
     *
     * @return EquippableItemTransformer
     */
    public function buildEquippableItemTransformer(): EquippableItemTransformer
    {
        return new EquippableItemTransformer(new ItemSocketTransformer(new CharacterGemDetailsService));
    }

    /**
     * Build a real ItemTransformer around the supplied enricher factory with its real Gem collaborators.
     *
     * @param ItemEnricherFactory $itemEnricherFactory
     * @return ItemTransformer
     */
    public function buildItemTransformer(ItemEnricherFactory $itemEnricherFactory): ItemTransformer
    {
        return new ItemTransformer(
            $itemEnricherFactory,
            new ItemSocketTransformer(new CharacterGemDetailsService),
            new GemAbilityDuplicateService,
        );
    }
}
