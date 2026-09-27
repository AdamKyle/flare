<?php

namespace Tests\Feature\Game\Character\CharacterInventory\Controllers\Api;

use App\Game\Core\Currency\Values\CurrencyCacheType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateAlchemyBagSlot;
use Tests\Traits\CreateItem;

class CharacterCurrencyCacheControllerTest extends TestCase
{
    use CreateAlchemyBagSlot, CreateItem, RefreshDatabase;

    public function test_using_an_owned_cache_returns_the_withdrawal_result(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['copper_coins' => 0]);
        $cacheItem = $this->createItem([
            'type' => 'alchemy',
            'usable' => true,
            'currency_cache_type' => CurrencyCacheType::COPPER_COINS,
            'cache_amount' => 300,
        ]);
        $slot = $this->createAlchemyBagSlot([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $cacheItem->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/currency-caches/use/'.$slot->id);

        $response->assertStatus(200);
        $response->assertJson([
            'withdrawn_amount' => 300,
            'remaining_cache_amount' => 0,
        ]);
        $this->assertEquals(300, $character->refresh()->copper_coins);
    }
}
