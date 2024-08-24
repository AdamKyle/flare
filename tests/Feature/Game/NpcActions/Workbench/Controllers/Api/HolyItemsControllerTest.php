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
        $character = $this->character->givePlayerLocation()
            ->inventoryManagement()
            ->giveItem(
                $this->createItem([
                    'holy_stacks' => 20,
                ])
            )
            ->giveItem(
                $this->createItem([
                    'type' => 'alchemy',
                    'holy_level' => 5,
                    'can_use_on_other_items' => true,
                ])
            )
            ->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/smiths-workbench');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['items']);
        $this->assertCount(1, $jsonData['alchemy_items']);
    }

    public function testApplyOil()
    {

        $item = $this->createItem([
            'type' => 'weapon',
            'holy_stacks' => 20,
        ]);

        $item = $item->refresh();

        $character = $this->character->inventoryManagement()->giveItem(
            $item
        )->giveItem($this->createItem([
            'type' => 'alchemy',
            'holy_level' => 1,
            'can_use_on_other_items' => true,
        ]))->getCharacter();

        $character->update([
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->type === 'weapon';
        })->first();

        $alchemy = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->type === 'alchemy';
        })->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/smithy-workbench/apply', [
                '_token' => csrf_token(),
                'item_id' => $slot->item->id,
                'alchemy_item_id' => $alchemy->item->id,
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $character = $character->refresh();

        $this->assertNotNull($character->inventory->slots->filter(function ($slot) {
            return $slot->item->holy_stacks_applied === 1;
        })->first());

        $this->assertCount(1, $jsonData['items']);
        $this->assertCount(0, $jsonData['alchemy_items']);
    }
}
