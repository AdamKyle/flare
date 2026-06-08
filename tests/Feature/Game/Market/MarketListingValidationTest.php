<?php

namespace Tests\Feature\Game\Market;

use App\Flare\Models\MarketBoard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class MarketListingValidationTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    public function test_negative_list_for_is_rejected(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem();
        $slot = $character->inventory->slots()->create(['item_id' => $item->id]);

        $response = $this->actingAs($character->user)->call('POST', '/api/market-board/sell-item/'.$character->id, [
            'slot_id' => $slot->id,
            'list_for' => -1,
        ]);

        $response->assertStatus(302);
    }

    public function test_zero_list_for_is_rejected(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem();
        $slot = $character->inventory->slots()->create(['item_id' => $item->id]);

        $response = $this->actingAs($character->user)->call('POST', '/api/market-board/sell-item/'.$character->id, [
            'slot_id' => $slot->id,
            'list_for' => 0,
        ]);

        $response->assertStatus(302);
    }

    public function test_negative_list_for_does_not_create_market_listing(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem();
        $slot = $character->inventory->slots()->create(['item_id' => $item->id]);

        $this->actingAs($character->user)->call('POST', '/api/market-board/sell-item/'.$character->id, [
            'slot_id' => $slot->id,
            'list_for' => -100,
        ]);

        $this->assertSame(0, MarketBoard::count());
    }

    public function test_valid_positive_listing_still_works(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem(['cost' => 100]);
        $slot = $character->inventory->slots()->create(['item_id' => $item->id]);

        $response = $this->actingAs($character->user)->call('POST', '/api/market-board/sell-item/'.$character->id, [
            'slot_id' => $slot->id,
            'list_for' => 1000,
        ]);

        $response->assertStatus(200);
        $this->assertSame(1000, MarketBoard::first()->listed_price);
    }
}
