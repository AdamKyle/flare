<?php

namespace Tests\Feature\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\AlchemyBagSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateKingdom;

class AttackKingdomTest extends TestCase
{
    use CreateItem, CreateKingdom, RefreshDatabase;

    public function test_fetch_attacking_data_returns_damaging_alchemy_bag_stacks_with_amount(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $enemy = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $kingdom = $this->createKingdom([
            'character_id' => $enemy->id,
            'game_map_id' => $character->map->game_map_id,
        ]);
        $item = $this->createItem([
            'type' => 'alchemy',
            'damages_kingdoms' => true,
            'kingdom_damage' => 0.25,
        ]);
        $slot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 8,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/fetch-attacking-data/'.$kingdom->id.'/'.$character->id);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($slot->id, $responseData['items_to_use'][0]['id']);
        $this->assertEquals(8, $responseData['items_to_use'][0]['amount']);
        $this->assertEquals($item->id, $responseData['items_to_use'][0]['item']['id']);
    }
}
