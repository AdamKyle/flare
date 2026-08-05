<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class AlchemyControllerTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?Character $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->getCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_paginated_items_endpoint_respects_per_page_and_search()
    {
        $this->createItem([
            'name' => 'Alpha Elixir',
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'skill_level_required' => 0,
        ]);
        $this->createItem([
            'name' => 'Beta Elixir',
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'skill_level_required' => 0,
        ]);

        $firstPage = $this->actingAs($this->character->user)
            ->call('GET', '/api/alchemy/'.$this->character->id.'/items', [
                'per_page' => 1,
                'page' => 1,
            ]);

        $firstPageData = json_decode($firstPage->getContent(), true);

        $searchResponse = $this->actingAs($this->character->user)
            ->call('GET', '/api/alchemy/'.$this->character->id.'/items', [
                'per_page' => 15,
                'page' => 1,
                'search_text' => 'Beta',
            ]);

        $searchData = json_decode($searchResponse->getContent(), true);

        $this->assertEquals(200, $firstPage->status());
        $this->assertCount(1, $firstPageData['data']);
        $this->assertTrue($firstPageData['meta']['can_load_more']);
        $this->assertCount(1, $searchData['data']);
        $this->assertEquals('Beta Elixir', $searchData['data'][0]['name']);
    }

    public function test_transmute_success_returns_alchemy_result(): void
    {
        $item = $this->createItem([
            'name' => 'Alpha Elixir',
            'crafting_type' => 'alchemy',
            'can_craft' => true,
            'skill_level_required' => 0,
            'skill_level_trivial' => 0,
            'gold_dust_cost' => 0,
            'shards_cost' => 0,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/transmute/'.$this->character->id, [
                'item_to_craft' => $item->id,
            ]);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertNotNull($data['alchemy_result']);
        $this->assertSame($item->id, $data['alchemy_result']['item_id']);
        $this->assertSame($item->name, $data['alchemy_result']['name']);
        $this->assertSame(1, $data['alchemy_result']['amount_created']);
        $this->assertSame(1, $data['alchemy_result']['current_amount']);
    }

    public function test_transmute_for_missing_item_returns_null_alchemy_result(): void
    {
        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/transmute/'.$this->character->id, [
                'item_to_craft' => 999999,
            ]);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertNull($data['alchemy_result']);
    }
}
