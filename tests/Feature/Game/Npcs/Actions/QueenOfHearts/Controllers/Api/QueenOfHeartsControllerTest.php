<?php

namespace Tests\Feature\Game\Npcs\Actions\QueenOfHearts\Controllers\Api;

use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Items\Values\RandomAffixTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class QueenOfHeartsControllerTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateItemAffix, RefreshDatabase;

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

    public function test_initial_inventory_response_includes_slots_and_cost_lookups()
    {
        $this->character->givePlayerLocation();

        $uniqueItem = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => true,
            ])->id,
        ]);

        $nonUniqueItem = $this->createItem([
            'type' => 'weapon',
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($uniqueItem)
            ->giveItem($nonUniqueItem)
            ->getCharacter();

        $slot = $character->inventory->slots()->where('item_id', $uniqueItem->id)->first();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/uniques');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['unique_slots']);
        $this->assertCount(1, $jsonData['non_unique_slots']);
        $this->assertArrayHasKey('reroll', $jsonData['costs']);
        $this->assertArrayHasKey('movement', $jsonData['costs']);
        $this->assertArrayHasKey($slot->id, $jsonData['costs']['movement']);
    }

    public function test_successful_reroll_response_retains_the_full_cost_lookup()
    {
        $hellMap = $this->createGameMap(['name' => 'Hell', 'path' => 'hell-path']);

        $this->character->givePlayerLocation(16, 16, $hellMap);

        $uniqueItem = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($uniqueItem)
            ->getCharacter();

        $character->update([
            'gold_dust' => 1000000,
            'shards' => 10000,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots()->where('item_id', $uniqueItem->id)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/random-enchant/reroll', [
                'selected_slot_id' => $slot->id,
                'selected_affix' => 'prefix',
                'selected_reroll_type' => 'base',
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->status());
        $this->assertArrayHasKey('reroll', $jsonData['costs']);
        $this->assertArrayHasKey('movement', $jsonData['costs']);
    }

    public function test_successful_movement_response_retains_the_full_cost_lookup()
    {
        $hellMap = $this->createGameMap(['name' => 'Hell', 'path' => 'hell-path']);

        $this->character->givePlayerLocation(16, 16, $hellMap);

        $accessItem = $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::QUEEN_OF_HEARTS->value,
        ]);

        $uniqueItemToMove = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
        ]);

        $untouchedUniqueItem = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
        ]);

        $nonUniqueItem = $this->createItem([
            'type' => 'weapon',
        ]);

        $character = $this->character
            ->inventoryManagement()
            ->giveItem($accessItem)
            ->giveItem($uniqueItemToMove)
            ->giveItem($untouchedUniqueItem)
            ->giveItem($nonUniqueItem)
            ->getCharacter();

        $character->update([
            'gold_dust' => 1000000,
            'shards' => 10000,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots()->where('item_id', $uniqueItemToMove->id)->first();
        $secondSlot = $character->inventory->slots()->where('item_id', $nonUniqueItem->id)->first();
        $untouchedSlot = $character->inventory->slots()->where('item_id', $untouchedUniqueItem->id)->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/random-enchant/move', [
                'selected_slot_id' => $slot->id,
                'selected_secondary_slot_id' => $secondSlot->id,
                'selected_affix' => 'prefix',
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->status());
        $this->assertArrayHasKey('reroll', $jsonData['costs']);
        $this->assertArrayHasKey('movement', $jsonData['costs']);
        $this->assertArrayHasKey($untouchedSlot->id, $jsonData['costs']['movement']);
    }
}
