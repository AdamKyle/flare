<?php

namespace Tests\Feature\Game\Skills\Controllers\Api;

use App\Flare\Models\Character;
use App\Flare\Models\ItemSkill;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class TrinketCraftingControllerTest extends TestCase
{
    use CreateGameSkill, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?Character $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $trinketrySkill = $this->createGameSkill([
            'name' => 'Trinketry',
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->assignSkill($trinketrySkill, 10)
            ->getCharacter();
    }

    protected function tearDown(): void
    {
        Model::preventLazyLoading(false);

        parent::tearDown();

        $this->character = null;
    }

    public function test_paginated_items_endpoint_respects_per_page_and_search()
    {
        $this->createItem([
            'name' => 'Alpha Trinket',
            'type' => 'trinket',
            'skill_level_required' => 0,
        ]);
        $this->createItem([
            'name' => 'Beta Trinket',
            'type' => 'trinket',
            'skill_level_required' => 0,
        ]);

        $firstPage = $this->actingAs($this->character->user)
            ->call('GET', '/api/trinket-crafting/'.$this->character->id.'/items', [
                'per_page' => 1,
                'page' => 1,
            ]);

        $firstPageData = json_decode($firstPage->getContent(), true);

        $searchResponse = $this->actingAs($this->character->user)
            ->call('GET', '/api/trinket-crafting/'.$this->character->id.'/items', [
                'per_page' => 15,
                'page' => 1,
                'search_text' => 'Beta',
            ]);

        $searchData = json_decode($searchResponse->getContent(), true);

        $this->assertEquals(200, $firstPage->status());
        $this->assertCount(1, $firstPageData['data']);
        $this->assertTrue($firstPageData['meta']['can_load_more']);
        $this->assertCount(1, $searchData['data']);
        $this->assertEquals('Beta Trinket', $searchData['data'][0]['name']);
        $this->assertArrayHasKey('preview', $searchData['data'][0]);
    }

    public function test_craft_trinket_success_returns_result_preview(): void
    {
        $trinket = $this->createItem([
            'name' => 'Lucky Charm',
            'type' => 'trinket',
            'skill_level_required' => 0,
            'skill_level_trivial' => 0,
            'gold_dust_cost' => 0,
            'copper_coin_cost' => 0,
        ]);

        $response = $this->actingAs($this->character->user)
            ->call('POST', '/api/trinket-craft/'.$this->character->id, [
                'item_to_craft' => $trinket->id,
            ]);

        $data = json_decode($response->getContent(), true);

        $response->assertOk();
        $this->assertNotNull($data['result_preview']);
        $this->assertSame($trinket->id, $data['result_preview']['item_id']);
    }

    public function test_paginated_items_endpoint_with_populated_rows_does_not_lazy_load(): void
    {
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $suffix = $this->createItemAffix(['type' => 'suffix']);

        $itemSkill = ItemSkill::create([
            'name' => 'Trinket Mastery',
            'description' => 'Increases trinket proficiency.',
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $decoratedTrinket = $this->createItem([
            'name' => 'Enchanted Trinket',
            'type' => 'trinket',
            'skill_level_required' => 0,
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => $suffix->id,
            'holy_stacks' => 5,
        ]);

        $decoratedTrinket->appliedHolyStacks()->create([
            'item_id' => $decoratedTrinket->id,
            'devouring_darkness_bonus' => 0.1,
            'stat_increase_bonus' => 0.1,
        ]);

        $decoratedTrinket->itemSkillProgressions()->create([
            'item_id' => $decoratedTrinket->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 1,
            'current_kill' => 10,
            'is_training' => false,
        ]);

        $this->createItem([
            'name' => 'Plain Trinket',
            'type' => 'trinket',
            'skill_level_required' => 0,
        ]);

        Model::preventLazyLoading();

        $response = $this->actingAs($this->character->user)
            ->call('GET', '/api/trinket-crafting/'.$this->character->id.'/items', [
                'per_page' => 15,
                'page' => 1,
            ]);

        $response->assertOk();

        $data = json_decode($response->getContent(), true);

        $this->assertCount(2, $data['data']);
        $this->assertArrayHasKey('preview', $data['data'][0]);
    }
}
