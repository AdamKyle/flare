<?php

namespace Tests\Unit\Game\Core\Traits;

use App\Game\Battle\Services\BattleDrop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class CanHaveQuestItemTest extends TestCase
{
    use CreateItem, CreateNpc, CreateQuest, RefreshDatabase;

    public function test_can_receive_item_returns_true_for_an_already_owned_non_quest_item(): void
    {
        $weapon = $this->createItem(['type' => 'weapon']);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($weapon)
            ->getCharacter();

        $this->assertTrue(BattleDrop::canReceiveItem($character, $weapon->id));
    }

    public function test_can_receive_item_returns_false_for_an_already_owned_quest_item(): void
    {
        $questItem = $this->createItem(['type' => 'quest']);

        $character = (new CharacterFactory())
            ->createBaseCharacter()
            ->inventoryManagement()
            ->giveItem($questItem)
            ->getCharacter();

        $this->assertFalse(BattleDrop::canReceiveItem($character, $questItem->id));
    }

    public function test_can_receive_item_returns_true_for_unowned_quest_item_needed_by_an_incomplete_quest(): void
    {
        $questItem = $this->createItem(['type' => 'quest']);

        $npc = $this->createNpc();

        $this->createQuest([
            'npc_id' => $npc->id,
            'item_id' => $questItem->id,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $this->assertTrue(BattleDrop::canReceiveItem($character, $questItem->id));
    }

    public function test_can_receive_item_returns_false_for_quest_item_tied_to_an_already_completed_quest(): void
    {
        $questItem = $this->createItem(['type' => 'quest']);

        $npc = $this->createNpc();

        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'item_id' => $questItem->id,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $this->createCompletedQuest([
            'character_id' => $character->id,
            'quest_id' => $quest->id,
        ]);

        $this->assertFalse(BattleDrop::canReceiveItem($character, $questItem->id));
    }
}
