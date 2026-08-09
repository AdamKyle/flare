<?php

namespace Tests\Feature\Game\Shop\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class GoblinShopControllerTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    public function test_fetch_items_returns_paginated_goblin_shop_items(): void
    {
        $this->createItem(['gold_bars_cost' => 500]);

        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $response = $this->actingAs($character->user)
            ->getJson('/api/goblin-shop/list-items/'.$character->id);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('can_load_more', $data['meta']);
    }

    public function test_purchase_item_deducts_gold_bars_and_gives_item(): void
    {
        $item = $this->createItem(['gold_bars_cost' => 500, 'type' => 'shield']);

        $character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom(['gold_bars' => 1000])
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->postJson('/api/goblin-shop/buy-item/'.$character->id.'/'.$item->id);

        $jsonData = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertStringStartsWith('Purchased:', $jsonData['message']);
        $this->assertSame(500, $character->refresh()->kingdoms->first()->gold_bars);
    }
}
