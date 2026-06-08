<?php

namespace Tests\Feature\Game\Shop;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ShopPurchaseValidationTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    public function test_negative_amount_is_rejected(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem(['cost' => 100]);

        $response = $this->actingAs($character->user)->call('POST', '/api/shop/purchase/multiple/' . $character->id, [
            'item_id' => $item->id,
            'amount' => -1,
        ]);

        $response->assertStatus(302);
    }

    public function test_zero_amount_is_rejected(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $item = $this->createItem(['cost' => 100]);

        $response = $this->actingAs($character->user)->call('POST', '/api/shop/purchase/multiple/' . $character->id, [
            'item_id' => $item->id,
            'amount' => 0,
        ]);

        $response->assertStatus(302);
    }

    public function test_character_gold_is_unchanged_after_rejected_negative_amount(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000]);
        $item = $this->createItem(['cost' => 100]);

        $this->actingAs($character->user)->call('POST', '/api/shop/purchase/multiple/' . $character->id, [
            'item_id' => $item->id,
            'amount' => -2,
        ]);

        $this->assertSame(1000, $character->refresh()->gold);
    }

    public function test_valid_positive_amount_still_purchases_multiple_items(): void
    {
        Event::fake();

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
        $character->update(['gold' => 1000]);
        $item = $this->createItem(['cost' => 100]);

        $response = $this->actingAs($character->user)->call('POST', '/api/shop/purchase/multiple/' . $character->id, [
            'item_id' => $item->id,
            'amount' => 2,
        ]);

        $response->assertStatus(200);
        $this->assertSame(800, $character->refresh()->gold);
        $this->assertSame(2, $character->inventory->slots()->where('item_id', $item->id)->count());
    }
}
