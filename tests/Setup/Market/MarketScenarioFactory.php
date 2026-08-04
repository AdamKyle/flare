<?php

namespace Tests\Setup\Market;

use App\Flare\Models\MarketBoard;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMarketBoardListing;

class MarketScenarioFactory
{
    use CreateItem, CreateLocation, CreateMarketBoardListing;

    private CharacterFactory $buyer;

    private CharacterFactory $seller;

    private MarketBoard $listing;

    /**
     * Builds a buyer, a seller, a listed item, and a Market Board listing
     * for that item owned by the seller.
     */
    public function createScenario(array $buyerAttributes = [], array $listedItemAttributes = [], int $listedPrice = 100): MarketScenarioFactory
    {
        $this->buyer = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->updateCharacter($buyerAttributes);
        $this->seller = (new CharacterFactory)->createBaseCharacter();

        $buyerCharacter = $this->buyer->getCharacter();

        $this->createLocation([
            'x' => $buyerCharacter->map->character_position_x,
            'y' => $buyerCharacter->map->character_position_y,
            'game_map_id' => $buyerCharacter->map->game_map_id,
            'is_port' => true,
        ]);

        $listedItem = $this->createItem($listedItemAttributes);

        $this->listing = $this->createMarketBoardListing([
            'character_id' => $this->seller->getCharacterId(),
            'item_id' => $listedItem->id,
            'listed_price' => $listedPrice,
            'is_locked' => false,
        ]);

        return $this;
    }

    public function buyer(): CharacterFactory
    {
        return $this->buyer;
    }

    public function seller(): CharacterFactory
    {
        return $this->seller;
    }

    public function listing(): MarketBoard
    {
        return $this->listing->refresh();
    }
}
