<?php

namespace Tests\Unit\Game\Factions\FactionLoyalty\Concerns;

use App\Flare\Models\Character;
use App\Game\BattleRewardProcessing\Handlers\FactionLoyaltyBountyHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;
use Tests\Traits\CreateFactionLoyalty;

class FactionLoyaltyTest extends TestCase
{
    use CreateFactionLoyalty, RefreshDatabase;

    private ?FactionLoyaltyBountyHandler $handler;

    private ?Character $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = resolve(FactionLoyaltyBountyHandler::class);
        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->handler = null;
        $this->character = null;
    }

    public function test_show_craft_for_npc_button_is_true_when_assisting_npc_has_matching_task(): void
    {
        $character = $this->character;

        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $character = $factionLoyaltyFactory->getCharacter();
        $assistingNpc = $factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $craftingItem = $factionLoyaltyFactory->getCraftingItemsForNpc($assistingNpc)[0];

        $result = $this->handler->showCraftForNpcButton($character, $craftingItem->type);

        $this->assertTrue($result);
    }

    public function test_show_craft_for_npc_button_is_false_when_no_matching_task(): void
    {
        $character = $this->character;

        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $character = $factionLoyaltyFactory->getCharacter();

        $result = $this->handler->showCraftForNpcButton($character, 'not-a-real-crafting-type');

        $this->assertFalse($result);
    }

    public function test_show_craft_for_npc_button_is_false_when_character_has_no_pledged_faction(): void
    {
        $character = $this->character;

        $result = $this->handler->showCraftForNpcButton($character, 'weapon');

        $this->assertFalse($result);
    }

    public function test_show_craft_for_npc_button_is_false_when_no_npc_is_currently_helping(): void
    {
        $character = $this->character;

        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $character = $factionLoyaltyFactory->getCharacter();
        $assistingNpc = $factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $assistingNpc->update(['currently_helping' => false]);

        $result = $this->handler->showCraftForNpcButton($character, 'weapon');

        $this->assertFalse($result);
    }

    public function test_has_incomplete_tasks_is_false_when_npc_has_no_task_record(): void
    {
        $character = $this->character;

        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $pledgedFactionLoyalty = $factionLoyaltyFactory->getPledgedFactionLoyalty();

        $npcWithoutTasks = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $pledgedFactionLoyalty->id,
            'npc_id' => $factionLoyaltyFactory->getFactionLoyaltyNpcs()[0]->npc_id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 0,
            'kingdom_item_defence_bonus' => 0.025,
            'currently_helping' => false,
        ]);

        $result = $this->handler->hasIncompleteTasks($npcWithoutTasks);

        $this->assertFalse($result);
    }

    public function test_get_faction_loyalty_returns_the_pledged_faction(): void
    {
        $character = $this->character;
        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $character = $factionLoyaltyFactory->getCharacter();
        $pledged = $factionLoyaltyFactory->getPledgedFactionLoyalty();

        $result = $this->handler->getFactionLoyalty($character);

        $this->assertSame($pledged->id, $result->id);
    }

    public function test_get_faction_loyalty_returns_null_when_nothing_pledged(): void
    {
        $result = $this->handler->getFactionLoyalty($this->character);

        $this->assertNull($result);
    }

    public function test_get_npc_currently_helping_returns_the_assisting_npc(): void
    {
        $character = $this->character;
        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $pledged = $factionLoyaltyFactory->getPledgedFactionLoyalty();
        $assistingNpc = $factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();

        $result = $this->handler->getNpcCurrentlyHelping($pledged->fresh());

        $this->assertSame($assistingNpc->id, $result->id);
    }

    public function test_has_matching_task_returns_true_for_a_matching_task(): void
    {
        $character = $this->character;
        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $assistingNpc = $factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $craftingItem = $factionLoyaltyFactory->getCraftingItemsForNpc($assistingNpc)[0];

        $result = $this->handler->hasMatchingTask($assistingNpc, 'item_id', $craftingItem->id);

        $this->assertTrue($result);
    }

    public function test_has_matching_task_returns_false_when_nothing_matches(): void
    {
        $character = $this->character;
        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $assistingNpc = $factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();

        $result = $this->handler->hasMatchingTask($assistingNpc, 'item_id', 999999);

        $this->assertFalse($result);
    }

    public function test_get_matching_task_returns_the_task_array(): void
    {
        $character = $this->character;
        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $assistingNpc = $factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $craftingItem = $factionLoyaltyFactory->getCraftingItemsForNpc($assistingNpc)[0];

        $task = $this->handler->getMatchingTask($assistingNpc, 'item_id', $craftingItem->id);

        $this->assertSame($craftingItem->id, $task['item_id']);
    }

    public function test_get_matching_task_returns_empty_array_when_nothing_matches(): void
    {
        $character = $this->character;
        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $assistingNpc = $factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();

        $task = $this->handler->getMatchingTask($assistingNpc, 'item_id', 999999);

        $this->assertSame([], $task);
    }

    public function test_update_matching_help_task_increases_current_amount_and_reports_fame_updated(): void
    {
        $character = $this->character;
        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $assistingNpc = $factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $craftingItem = $factionLoyaltyFactory->getCraftingItemsForNpc($assistingNpc)[0];

        $updatedNpc = $this->handler->updateMatchingHelpTask($assistingNpc, 'item_id', $craftingItem->id, 1);

        $task = $this->handler->getMatchingTask($updatedNpc, 'item_id', $craftingItem->id);

        $this->assertSame(1, $task['current_amount']);
        $this->assertTrue($this->handler->wasCurrentFameForTaskUpdated());
    }

    public function test_has_incomplete_tasks_is_true_when_a_task_is_not_yet_complete(): void
    {
        $character = $this->character;
        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $assistingNpc = $factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();

        $result = $this->handler->hasIncompleteTasks($assistingNpc);

        $this->assertTrue($result);
    }
}
