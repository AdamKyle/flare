<?php

namespace Tests\Unit\Game\Npcs\Actions\QueenOfHearts\Services;

use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Items\Values\RandomAffixTier;
use App\Game\Npcs\Actions\QueenOfHearts\Services\QueenOfHeartsService;
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

    private ?CharacterFactory $character = null;

    private ?QueenOfHeartsService $queenOfHeartsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter();
        $this->queenOfHeartsService = resolve(QueenOfHeartsService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->queenOfHeartsService = null;
    }

    public function test_re_roll_unique_rejects_when_character_is_not_in_hell(): void
    {
        Event::fake();

        $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::QUEEN_OF_HEARTS->value,
        ]);

        $character = $this->character->givePlayerLocation()->getCharacter();

        $result = $this->queenOfHeartsService->reRollUnique($character, 1, 'base', 'prefix');

        $this->assertStringContainsString('You need to be in Hell', $result['message']);
    }

    public function test_re_roll_unique_rejects_when_slot_not_found(): void
    {
        $hellMap = $this->createGameMap(['name' => 'Hell', 'path' => 'hell-path']);
        $this->character->givePlayerLocation(16, 16, $hellMap);

        $character = $this->character->getCharacter();

        $result = $this->queenOfHeartsService->reRollUnique($character, 999999, 'base', 'prefix');

        $this->assertStringContainsString('Unique does not exist', $result['message']);
    }

    public function test_re_roll_unique_rejects_when_character_cannot_afford_it(): void
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

        $character = $this->character->inventoryManagement()->giveItem($uniqueItem)->getCharacter();
        $character->update(['gold_dust' => 0, 'shards' => 0]);
        $character = $character->refresh();

        $slot = $character->inventory->slots()->where('item_id', $uniqueItem->id)->first();

        $result = $this->queenOfHeartsService->reRollUnique($character, $slot->id, 'base', 'prefix');

        $this->assertStringContainsString('don\'t like poor people', $result['message']);
    }

    public function test_move_affixes_rejects_when_character_is_not_in_hell(): void
    {
        Event::fake();

        $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::QUEEN_OF_HEARTS->value,
        ]);

        $character = $this->character->givePlayerLocation()->getCharacter();

        $result = $this->queenOfHeartsService->moveAffixes($character, 1, 2, 'prefix');

        $this->assertStringContainsString('You need to be in Hell', $result['message']);
    }

    public function test_move_affixes_rejects_when_either_slot_is_not_found(): void
    {
        $hellMap = $this->createGameMap(['name' => 'Hell', 'path' => 'hell-path']);
        $this->character->givePlayerLocation(16, 16, $hellMap);

        $questItem = $this->createItem(['type' => 'quest', 'effect' => ItemEffectType::QUEEN_OF_HEARTS->value]);

        $character = $this->character->inventoryManagement()->giveItem($questItem)->getCharacter();

        $result = $this->queenOfHeartsService->moveAffixes($character, 999999, 999998, 'prefix');

        $this->assertStringContainsString('Unique does not exist', $result['message']);
    }

    public function test_move_affixes_rejects_when_source_item_is_a_trinket(): void
    {
        $hellMap = $this->createGameMap(['name' => 'Hell', 'path' => 'hell-path']);
        $this->character->givePlayerLocation(16, 16, $hellMap);

        $trinket = $this->createItem(['type' => 'trinket']);
        $otherItem = $this->createItem(['type' => 'weapon']);
        $questItem = $this->createItem(['type' => 'quest', 'effect' => ItemEffectType::QUEEN_OF_HEARTS->value]);

        $character = $this->character->inventoryManagement()
            ->giveItem($trinket)
            ->giveItem($otherItem)
            ->giveItem($questItem)
            ->getCharacter();

        $trinketSlot = $character->inventory->slots()->where('item_id', $trinket->id)->first();
        $otherSlot = $character->inventory->slots()->where('item_id', $otherItem->id)->first();

        $result = $this->queenOfHeartsService->moveAffixes($character, $trinketSlot->id, $otherSlot->id, 'prefix');

        $this->assertStringContainsString('I don\'t know how to handle trinkets', $result['message']);
    }

    public function test_move_affixes_rejects_when_destination_item_is_a_trinket(): void
    {
        $hellMap = $this->createGameMap(['name' => 'Hell', 'path' => 'hell-path']);
        $this->character->givePlayerLocation(16, 16, $hellMap);

        $otherItem = $this->createItem(['type' => 'weapon']);
        $trinket = $this->createItem(['type' => 'trinket']);
        $questItem = $this->createItem(['type' => 'quest', 'effect' => ItemEffectType::QUEEN_OF_HEARTS->value]);

        $character = $this->character->inventoryManagement()
            ->giveItem($otherItem)
            ->giveItem($trinket)
            ->giveItem($questItem)
            ->getCharacter();

        $otherSlot = $character->inventory->slots()->where('item_id', $otherItem->id)->first();
        $trinketSlot = $character->inventory->slots()->where('item_id', $trinket->id)->first();

        $result = $this->queenOfHeartsService->moveAffixes($character, $otherSlot->id, $trinketSlot->id, 'prefix');

        $this->assertStringContainsString('I don\'t know how to handle trinkets', $result['message']);
    }

    public function test_move_affixes_rejects_when_character_cannot_afford_it(): void
    {
        $hellMap = $this->createGameMap(['name' => 'Hell', 'path' => 'hell-path']);
        $this->character->givePlayerLocation(16, 16, $hellMap);

        $sourceItem = $this->createItem([
            'type' => 'weapon',
            'item_prefix_id' => $this->createItemAffix([
                'type' => 'prefix',
                'randomly_generated' => true,
                'cost' => RandomAffixTier::LEGENDARY->value,
            ])->id,
        ]);
        $destinationItem = $this->createItem(['type' => 'weapon']);
        $questItem = $this->createItem(['type' => 'quest', 'effect' => ItemEffectType::QUEEN_OF_HEARTS->value]);

        $character = $this->character->inventoryManagement()
            ->giveItem($sourceItem)
            ->giveItem($destinationItem)
            ->giveItem($questItem)
            ->getCharacter();

        $character->update(['gold_dust' => 0, 'shards' => 0]);
        $character = $character->refresh();

        $sourceSlot = $character->inventory->slots()->where('item_id', $sourceItem->id)->first();
        $destinationSlot = $character->inventory->slots()->where('item_id', $destinationItem->id)->first();

        $result = $this->queenOfHeartsService->moveAffixes($character, $sourceSlot->id, $destinationSlot->id, 'prefix');

        $this->assertStringContainsString('so poor', $result['message']);
    }
}
