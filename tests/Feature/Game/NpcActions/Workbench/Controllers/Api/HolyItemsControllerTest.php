<?php

namespace Tests\Feature\Game\NpcActions\Workbench\Controllers\Api;

use App\Flare\Values\MaxCurrenciesValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class HolyItemsControllerTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?CharacterFactory $character = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetSmithingItems()
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

    public function testApplyOil()
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
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
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
}
