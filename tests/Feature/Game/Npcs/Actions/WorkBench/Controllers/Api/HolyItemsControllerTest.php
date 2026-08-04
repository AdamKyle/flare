<?php

namespace Tests\Feature\Game\Npcs\Actions\WorkBench\Controllers\Api;

use App\Flare\Models\AlchemyBagSlot;
use App\Game\Core\Currency\Services\CurrencyLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class HolyItemsControllerTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?CharacterFactory $character = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_get_smithing_items()
    {
        $oil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 5,
            'can_use_on_other_items' => true,
        ]);

        $character = $this->character->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem(
                $this->createItem([
                    'holy_stacks' => 20,
                ])
            )
            ->getCharacter();

        $character->alchemyBag->slots()->create([
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/smiths-workbench');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['items']);
        $this->assertCount(1, $jsonData['alchemy_items']);
        $this->assertEquals(1, $jsonData['alchemy_items'][0]['amount']);
    }

    public function test_get_smithing_items_includes_cost_lookup()
    {
        $oil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 3,
            'can_use_on_other_items' => true,
        ]);

        $item = $this->createItem([
            'holy_stacks' => 10,
        ]);

        $character = $this->character->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem($item)
            ->getCharacter();

        $alchemySlot = $character->alchemyBag->slots()->create([
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $itemSlot = $character->inventory->slots()->where('item_id', $item->id)->first();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/smiths-workbench');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals(3000, $jsonData['costs'][$itemSlot->id][$alchemySlot->id]);
    }

    public function test_apply_oil()
    {

        $item = $this->createItem([
            'type' => 'weapon',
            'holy_stacks' => 20,
        ]);

        $item = $item->refresh();

        $oil = $this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]);

        $character = $this->character->inventoryManagement()->giveItem(
            $item
        )->getCharacter();

        $alchemySlot = $character->alchemyBag->slots()->create([
            'character_id' => $character->id,
            'item_id' => $oil->id,
            'amount' => 1,
        ]);

        $character->update([
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->type === 'weapon';
        })->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/smithy-workbench/apply', [
                '_token' => csrf_token(),
                'item_id' => $slot->item->id,
                'alchemy_slot_id' => $alchemySlot->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $character = $character->refresh();

        $this->assertNotNull($character->inventory->slots->filter(function ($slot) {
            return $slot->item->holy_stacks_applied === 1;
        })->first());

        $this->assertCount(1, $jsonData['items']);
        $this->assertCount(0, $jsonData['alchemy_items']);
        $this->assertEquals(0, $character->alchemyBag->slots()->where('id', $alchemySlot->id)->count());
    }

    public function test_apply_oil_returns_representative_validation_error_response()
    {
        $item = $this->createItem([
            'type' => 'weapon',
            'holy_stacks' => 20,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $alchemySlot = AlchemyBagSlot::create([
            'alchemy_bag_id' => $character->alchemyBag->id,
            'character_id' => $character->id,
            'item_id' => $item->id,
            'amount' => 1,
        ]);

        $this->actingAs($character->user)
            ->json('POST', '/api/character/'.$character->id.'/smithy-workbench/apply', [
                'alchemy_slot_id' => $alchemySlot->id,
            ]);

        $response = $this->response;

        $response->assertStatus(422);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Error. Invalid Input.', $jsonData['errors']['item_id'][0]);
    }
}
