<?php

namespace Tests\Feature\Game\NpcActions\QueenOfHearts\Controllers\Api;

use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function test_uniques_only()
    {
        $character = $this->character->inventoryManagement()->giveItem($this->createItem([
            'item_suffix_id' => $this->createItemAffix(['randomly_generated' => true])->id,
        ]))->giveItem($this->createItem([
            'item_prefix_id' => $this->createItemAffix(['randomly_generated' => true])->id,
        ]))->giveItem($this->createItem([
            'item_prefix_id' => $this->createItemAffix(['randomly_generated' => false])->id,
        ]))->giveItem($this->createItem([
            'item_suffix_id' => $this->createItemAffix(['randomly_generated' => false])->id,
        ]))->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/character/'.$character->id.'/inventory/uniques');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(2, $jsonData['unique_slots']);
        $this->assertCount(2, $jsonData['non_unique_slots']);
    }

    public function test_re_roll()
    {
        Event::fake();

        $gameMap = $this->createGameMap(['name' => 'Hell']);

        $character = $this->character->inventoryManagement()
            ->giveItem($this->createItem(['effect' => ItemEffectsValue::QUEEN_OF_HEARTS]))
            ->giveItem($this->createItem([
                'item_prefix_id' => $this->createItemAffix([
                    'cost' => RandomAffixDetails::LEGENDARY,
                    'randomly_generated' => true,
                ]),
            ]))
            ->getCharacter();

        $character->map()->update(['game_map_id' => $gameMap->id]);

        $character->update(['gold' => MaxCurrenciesValue::MAX_GOLD, 'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST, 'shards' => MaxCurrenciesValue::MAX_SHARDS]);

        $slotWithUnique = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_unique;
        })->first();

        $result = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/random-enchant/reroll', [
                '_token' => csrf_token(),
                'selected_slot_id' => $slotWithUnique->id,
                'selected_affix' => 'all-enchantments',
                'selected_reroll_type' => 'everything',
            ]);

        $this->assertEquals(200, $result->getStatusCode());

        Event::assertDispatched(ServerMessageEvent::class);
    }

    public function test_move_affixes()
    {
        $questItem = $this->createItem(['effect' => ItemEffectsValue::QUEEN_OF_HEARTS, 'type' => 'quest']);

        $character = $this->character->inventoryManagement()
            ->giveItem($questItem)
            ->giveItem($this->createItem(['name' => 'Sample', 'type' => 'weapon']))
            ->giveItem($this->createItem([
                'item_suffix_id' => $this->createItemAffix([
                    'cost' => RandomAffixDetails::LEGENDARY,
                    'randomly_generated' => true,
                ]),
            ]))
            ->getCharacter();

        $character->update([
            'gold' => 0,
        ]);

        $gameMap = $this->createGameMap(['name' => 'Hell']);

        $character->map()->update(['game_map_id' => $gameMap->id]);

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
        ]);

        $character = $character->refresh();

        $slotWithUnique = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_unique;
        })->first();

        $slotToMoveTo = $character->inventory->slots->filter(function ($slot) {
            return ! $slot->item->is_unique && $slot->item->type !== 'quest';
        })->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/character/'.$character->id.'/random-enchant/move', [
                '_token' => csrf_token(),
                'type' => 'legendary',
                'selected_slot_id' => $slotWithUnique->id,
                'selected_secondary_slot_id' => $slotToMoveTo->id,
                'selected_affix' => 'all-enchantments',
            ]);

        $this->assertEquals(200, $response->getStatusCode());
    }
}
