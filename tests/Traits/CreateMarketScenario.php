<?php

namespace Tests\Traits;

use App\Flare\Models\Location;
use App\Flare\Models\MarketBoard;
use Tests\Setup\Character\CharacterFactory;

trait CreateMarketScenario
{
    public function createMarketScenario(array $buyerAttributes, array $listingItemAttributes = []): array
    {
        $buyer = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->updateCharacter($buyerAttributes)->getCharacter();
        $seller = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        Location::create([
            'name' => 'Market Port', 'game_map_id' => $buyer->map->game_map_id, 'description' => 'Port',
            'is_port' => true, 'can_players_enter' => true, 'can_auto_battle' => true,
            'x' => $buyer->x_position, 'y' => $buyer->y_position,
        ]);
        $listing = MarketBoard::create([
            'character_id' => $seller->id,
            'item_id' => $this->createItem($listingItemAttributes)->id,
            'listed_price' => 100,
            'is_locked' => false,
        ]);

        return [$buyer, $listing];
    }
}
