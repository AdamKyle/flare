<?php

namespace Tests\Unit\Game\NpcActions\QueenOfHeartsActions\Services;

use App\Flare\Models\Item;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\NpcActions\QueenOfHeartsActions\Services\QueenOfHeartsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class QueenOfHeartsServiceTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateItemAffix, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?QueenOfHeartsService $queenOfHeartsService;

    private ?Item $queenOfHeartsQuestItem;

    public function setUp(): void
    {

        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $this->queenOfHeartsService = resolve(QueenOfHeartsService::class);
        $this->queenOfHeartsQuestItem = $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::QUEEN_OF_HEARTS,
        ]);
    }

    public function tearDown(): void
    {

        parent::tearDown();

        $this->character = null;
        $this->queenOfHeartsService = null;
        $this->queenOfHeartsService = null;
    }

    public function testCannotReRollForItemThatDoesntExist()
    {
        $gameMap = $this->createGameMap(['name' => 'Hell']);

        $character = $this->character->getCharacter();

        $character->map()->update(['game_map_id' => $gameMap->id]);

        $character = $character->refresh();

        $result = $this->queenOfHeartsService->reRollUnique($character, 1, 'all-enchantments', 'everything');

        $this->assertEquals('Where did you put that item, child? Ooooh hooo hooo hooo! Are you playing hide and seek with it? (Unique does not exist.)', $result['message']);
        $this->assertEquals(422, $result['status']);
    }

    public function testCannotReRollWhenNotInHell()
    {
        Event::fake();

        $character = $this->character->inventoryManagement()->giveItem($this->createItem())->getCharacter();

        $slot = $character->inventory->slots()->first();

        $result = $this->queenOfHeartsService->reRollUnique($character, $slot->id, 'all-enchantments', 'everything');

        Event::assertDispatched(GlobalMessageEvent::class);

        $this->assertEquals('You need to be in Hell to access The Queen of Hearts and have the quest item: ' . $this->queenOfHeartsQuestItem->affix_name . '.', $result['message']);
        $this->assertEquals(422, $result['status']);
    }

    public function testCannotReRollWhenCantAfford()
    {
        $gameMap = $this->createGameMap(['name' => 'Hell']);

        $character = $this->character->inventoryManagement()
            ->giveItem($this->queenOfHeartsQuestItem)
            ->giveitem($this->createItem([
                'item_suffix_id' => $this->createItemAffix([
                    'cost' => RandomAffixDetails::LEGENDARY,
                    'randomly_generated' => true,
                ])
            ]))
            ->getCharacter();

        $character->map()->update(['game_map_id' => $gameMap->id]);

        $character->update(['gold' => 0]);

        $character = $character->refresh();

        $slotWithUnique = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_unique;
        })->first();

        $result = $this->queenOfHeartsService->reRollUnique($character, $slotWithUnique->id, 'all-enchantments', 'everything');

        $this->assertEquals('What! No! Child! I don\'t like poor people. I don\'t even date poor men! Oh this is so saddening, child! (You don\'t have enough currency, you made the Queen sad.)', $result['message']);
        $this->assertEquals(422, $result['status']);
    }

    public function testCanReRoll()
    {
        Event::fake();

        $gameMap = $this->createGameMap(['name' => 'Hell']);

        $character = $this->character->inventoryManagement()
            ->giveItem($this->queenOfHeartsQuestItem)
            ->giveitem($this->createItem([
                'item_suffix_id' => $this->createItemAffix([
                    'cost' => RandomAffixDetails::LEGENDARY,
                    'randomly_generated' => true,
                ])
            ]))
            ->getCharacter();

        $character->map()->update(['game_map_id' => $gameMap->id]);

        $character->update(['gold' => MaxCurrenciesValue::MAX_GOLD, 'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST, 'shards' => MaxCurrenciesValue::MAX_SHARDS]);

        $character = $character->refresh();

        $slotWithUnique = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_unique;
        })->first();

        $result = $this->queenOfHeartsService->reRollUnique($character, $slotWithUnique->id, 'everything', 'all-enchantments');

        Event::assertDispatched(ServerMessageEvent::class);

        $this->assertEquals(200, $result['status']);
    }

    public function testCannotMoveEnchantmentsWhenNotInHell()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $result = $this->queenOfHeartsService->moveAffixes($character, 1, 1, 'all-enchantments');

        Event::assertDispatched(GlobalMessageEvent::class);

        $this->assertEquals('You need to be in Hell to access The Queen of Hearts and have the quest item: ' . $this->queenOfHeartsQuestItem->affix_name . '.', $result['message']);
        $this->assertEquals(422, $result['status']);
    }

    public function testCannotMoveEnchantmentsWhenItemsDoNotExist()
    {
        $questItem = $this->queenOfHeartsQuestItem;

        $character = $this->character->inventoryManagement()->giveItem($questItem)->getCharacter();

        $character->update([
            'gold' => 0,
        ]);

        $gameMap = $this->createGameMap(['name' => 'Hell']);

        $character->map()->update(['game_map_id' => $gameMap->id]);

        $character = $character->refresh();

        $result = $this->queenOfHeartsService->moveAffixes($character, 1, 1, 'all-enchantments');

        $this->assertEquals('Where did you put that item, child? Ooooh hooo hooo hooo! Are you playing hide and seek with it? (Unique does not exist.)', $result['message']);
        $this->assertEquals(422, $result['status']);
    }

    public function testCannotMoveEnchantmentsWhenCannotAfford()
    {
        $questItem = $this->queenOfHeartsQuestItem;

        $character = $this->character->inventoryManagement()
            ->giveItem($questItem)
            ->giveItem($this->createItem(['name' => 'Sample', 'type' => 'weapon']))
            ->giveitem($this->createItem([
                'item_suffix_id' => $this->createItemAffix([
                    'cost' => RandomAffixDetails::LEGENDARY,
                    'randomly_generated' => true,
                ])
            ]))
            ->getCharacter();

        $character->update([
            'gold' => 0,
        ]);

        $gameMap = $this->createGameMap(['name' => 'Hell']);

        $character->map()->update(['game_map_id' => $gameMap->id]);

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
        ]);

        $character->update(['gold' => 0]);

        $character = $character->refresh();

        $slotWithUnique = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_unique;
        })->first();

        $slotToMoveTo = $character->inventory->slots->filter(function ($slot) {
            return ! $slot->item->is_unique;
        })->first();

        $result = $this->queenOfHeartsService->moveAffixes($character, $slotWithUnique->id, $slotToMoveTo->id, 'all-enchantments');

        $this->assertEquals('Child, you are so poor (Not enough currency) ...', $result['message']);
        $this->assertEquals(422, $result['status']);
    }

    public function testCannotMoveEnchantmentsWhenInvalidItemTypeForItemToMoveTo()
    {
        $questItem = $this->queenOfHeartsQuestItem;

        $character = $this->character->inventoryManagement()
            ->giveItem($questItem)
            ->giveItem($this->createItem(['name' => 'Sample', 'type' => 'artifact']))
            ->giveitem($this->createItem([
                'item_suffix_id' => $this->createItemAffix([
                    'cost' => RandomAffixDetails::LEGENDARY,
                    'randomly_generated' => true,
                ])
            ]))
            ->getCharacter();

        $gameMap = $this->createGameMap(['name' => 'Hell']);

        $character->map()->update(['game_map_id' => $gameMap->id]);

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
        ]);

        $character->update(['gold' => MaxCurrenciesValue::MAX_GOLD]);

        $character = $character->refresh();

        $slotWithUnique = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_unique;
        })->first();

        $slotToMoveTo = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->type === 'artifact';
        })->first();

        $result = $this->queenOfHeartsService->moveAffixes($character, $slotWithUnique->id, $slotToMoveTo->id, 'all-enchantments');

        $this->assertEquals('I don\'t know how to handle trinkets or artifacts child. Bring me something sexy! Oooooh hooo hooo!', $result['message']);
        $this->assertEquals(422, $result['status']);
    }

    public function testCannotMoveEnchantmentsWhenInvalidItemTypeForItemToMoveFrom()
    {
        $questItem = $this->queenOfHeartsQuestItem;

        $character = $this->character->inventoryManagement()
            ->giveItem($questItem)
            ->giveItem($this->createItem(['name' => 'Sample', 'type' => 'spell-damage']))
            ->giveitem($this->createItem([
                'item_suffix_id' => $this->createItemAffix([
                    'cost' => RandomAffixDetails::LEGENDARY,
                    'randomly_generated' => true,
                ])
            ]))
            ->getCharacter();

        $gameMap = $this->createGameMap(['name' => 'Hell']);

        $character->map()->update(['game_map_id' => $gameMap->id]);

        $character->update([
            'gold' => MaxCurrenciesValue::MAX_GOLD,
        ]);

        $character->update(['gold' => MaxCurrenciesValue::MAX_GOLD]);

        $character = $character->refresh();

        $slotWithUnique = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->is_unique;
        })->first();

        $slotWithUnique->item()->update([
            'type' => 'artifact',
        ]);

        $slotWithUnique = $slotWithUnique->refresh();

        $slotToMoveTo = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->type === 'spell-damage';
        })->first();

        $result = $this->queenOfHeartsService->moveAffixes($character, $slotWithUnique->id, $slotToMoveTo->id, 'all-enchantments');

        $this->assertEquals('I don\'t know how to handle trinkets or artifacts child. Bring me something sexy! Oooooh hooo hooo!', $result['message']);
        $this->assertEquals(422, $result['status']);
    }

    public function testCanMoveEnchantments()
    {
        $questItem = $this->queenOfHeartsQuestItem;

        $character = $this->character->inventoryManagement()
            ->giveItem($questItem)
            ->giveItem($this->createItem(['name' => 'Sample', 'type' => 'weapon']))
            ->giveitem($this->createItem([
                'item_suffix_id' => $this->createItemAffix([
                    'cost' => RandomAffixDetails::LEGENDARY,
                    'randomly_generated' => true,
                ])
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
            return ! $slot->item->is_unique;
        })->first();

        $result = $this->queenOfHeartsService->moveAffixes($character, $slotWithUnique->id, $slotToMoveTo->id, 'all-enchantments');

        $this->assertEquals(200, $result['status']);
    }
}
