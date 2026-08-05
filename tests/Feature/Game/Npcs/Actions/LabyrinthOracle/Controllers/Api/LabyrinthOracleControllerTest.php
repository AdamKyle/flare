<?php

namespace Tests\Feature\Game\Npcs\Actions\LabyrinthOracle\Controllers\Api;

use App\Flare\Models\ItemSkill;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class LabyrinthOracleControllerTest extends TestCase
{
    use CreateGem, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_inventory_items()
    {
        $basicItem = $this->createItem([
            'name' => 'basic item',
        ]);
        $artifactItem = $this->createItem([
            'name' => 'artifact item',
            'type' => 'artifact',
        ]);
        $trinketItem = $this->createItem([
            'name' => 'trinket item',
            'type' => 'trinket',
        ]);
        $enchantedItem = $this->createItem([
            'name' => 'enchanted item',
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
            ])->id,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($basicItem)
            ->giveItem($enchantedItem)
            ->giveItem($trinketItem)
            ->giveItem($artifactItem)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/labyrinth-oracle');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(2, $jsonData['inventory']);
    }

    public function test_inventory_items_with_one_of_each()
    {
        $basicItem = $this->createItem([
            'name' => 'basic item',
        ]);
        $artifactItem = $this->createItem([
            'name' => 'artifact item',
            'type' => 'artifact',
        ]);
        $trinketItem = $this->createItem([
            'name' => 'trinket item',
            'type' => 'trinket',
        ]);
        $enchantedItem = $this->createItem([
            'name' => 'enchanted item',
            'item_suffix_id' => $this->createItemAffix([
                'type' => 'suffix',
            ])->id,
        ]);

        $gemItem = $this->createItem([
            'name' => 'gem item',
            'socket_count' => 1,
        ]);

        $gemItem->sockets()->create([
            'gem_id' => $this->createGem()->id,
            'item_id' => $gemItem->id,
        ]);

        $gemItem = $gemItem->refresh();

        $holyItem = $this->createItem([
            'name' => 'holy item',
        ]);

        $holyItem->appliedHolyStacks()->create([
            'item_id' => $holyItem->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $holyItem = $holyItem->refresh();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($basicItem)
            ->giveItem($enchantedItem)
            ->giveItem($trinketItem)
            ->giveItem($artifactItem)
            ->giveItem($gemItem)
            ->giveItem($holyItem)
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/labyrinth-oracle');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(4, $jsonData['inventory']);
    }

    public function test_paginated_items_endpoint_respects_ownership_pagination_and_search()
    {
        $itemOne = $this->createItem(['name' => 'Alpha Item']);
        $itemTwo = $this->createItem(['name' => 'Beta Item']);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemOne)
            ->giveItem($itemTwo)
            ->getCharacter();

        $otherCharacter = (new CharacterFactory)->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($this->createItem(['name' => 'Other Character Item']))
            ->getCharacter();

        $firstPage = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/labyrinth-oracle/items', [
                'per_page' => 1,
                'page' => 1,
            ]);

        $firstPageData = json_decode($firstPage->getContent(), true);

        $searchResponse = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/labyrinth-oracle/items', [
                'per_page' => 15,
                'page' => 1,
                'search_text' => 'Beta',
            ]);

        $searchData = json_decode($searchResponse->getContent(), true);

        $this->assertEquals(200, $firstPage->status());
        $this->assertCount(1, $firstPageData['data']);
        $this->assertTrue($firstPageData['meta']['can_load_more']);
        $this->assertCount(1, $searchData['data']);
        $this->assertEquals($itemTwo->id, $searchData['data'][0]['id']);
        $this->assertNotEquals($otherCharacter->id, $character->id);
    }

    public function test_inventory_items_includes_transfer_costs()
    {
        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/labyrinth-oracle');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals([
            'gold' => 100_000_000,
            'shards' => 5_000,
            'gold_dust' => 2_500,
        ], $jsonData['costs']);
    }

    public function test_use_labyrinth_oracle_to_transfer_item()
    {
        Event::fake();

        $attachedSuffix = $this->createItemAffix([
            'type' => 'suffix',
        ]);

        $attachedPrefix = $this->createItemAffix([
            'type' => 'prefix',
        ]);

        $itemToTransferFrom = $this->createItem([
            'item_suffix_id' => $attachedSuffix->id,
            'item_prefix_id' => $attachedPrefix->id,
            'socket_count' => 2,
        ]);

        $itemToTransferFrom->appliedHolyStacks()->create([
            'item_id' => $itemToTransferFrom->id,
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $itemToTransferFrom = $itemToTransferFrom->refresh();

        $gemToAttach = $this->createGem();

        $itemToTransferFrom->sockets()->create([
            'item_id' => $itemToTransferFrom->id,
            'gem_id' => $gemToAttach->id,
        ]);

        $itemToTransferFrom = $itemToTransferFrom->refresh();
        $itemToTransferTo = $this->createItem();

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($itemToTransferFrom)
            ->giveItem($itemToTransferTo)
            ->getCharacter();

        $character->update([
            'gold' => CurrencyLimit::MAX_GOLD,
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/transfer-attributes', [
                'item_id_from' => $itemToTransferFrom->id,
                'item_id_to' => $itemToTransferTo->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(2, count($jsonData['inventory']));
        $this->assertNotNull($jsonData['source_result_preview']);
        $this->assertNotNull($jsonData['destination_result_preview']);
        $this->assertNotSame(
            $jsonData['source_result_preview']['item_id'],
            $jsonData['destination_result_preview']['item_id']
        );

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_paginated_items_endpoint_with_populated_rows_does_not_lazy_load()
    {
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $suffix = $this->createItemAffix(['type' => 'suffix']);

        $itemSkill = ItemSkill::create([
            'name' => 'Item Mastery',
            'description' => 'Increases item proficiency.',
            'max_level' => 10,
            'total_kills_needed' => 100,
        ]);

        $decoratedItem = $this->createItem([
            'name' => 'Decorated Item',
            'item_prefix_id' => $prefix->id,
            'item_suffix_id' => $suffix->id,
            'holy_stacks' => 5,
        ]);

        $decoratedItem->appliedHolyStacks()->create([
            'item_id' => $decoratedItem->id,
            'devouring_darkness_bonus' => 0.1,
            'stat_increase_bonus' => 0.1,
        ]);

        $decoratedItem->itemSkillProgressions()->create([
            'item_id' => $decoratedItem->id,
            'item_skill_id' => $itemSkill->id,
            'current_level' => 1,
            'current_kill' => 10,
            'is_training' => false,
        ]);

        $decoratedItem = $decoratedItem->refresh();

        $plainItem = $this->createItem(['name' => 'Plain Item']);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($decoratedItem)
            ->giveItem($plainItem)
            ->getCharacter();

        Model::preventLazyLoading();

        try {
            $response = $this->actingAs($character->user)
                ->call('GET', '/api/character/'.$character->id.'/labyrinth-oracle/items', [
                    'per_page' => 15,
                    'page' => 1,
                ]);

            $response->assertOk();

            $data = json_decode($response->getContent(), true);

            $this->assertCount(2, $data['data']);
            $this->assertArrayHasKey('preview', $data['data'][0]);
        } finally {
            Model::preventLazyLoading(false);
        }
    }
}
