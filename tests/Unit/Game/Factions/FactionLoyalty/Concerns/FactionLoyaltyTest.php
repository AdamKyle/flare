<?php

namespace Tests\Unit\Game\Factions\FactionLoyalty\Concerns;

use App\Game\BattleRewardProcessing\Handlers\FactionLoyaltyBountyHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\FactionLoyalty\FactionLoyaltyFactory;
use Tests\TestCase;
use Tests\Traits\CreateFactionLoyalty;

class FactionLoyaltyTest extends TestCase
{
    use CreateFactionLoyalty, RefreshDatabase;

    private FactionLoyaltyBountyHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = resolve(FactionLoyaltyBountyHandler::class);
    }

    public function test_show_craft_for_npc_button_is_true_when_assisting_npc_has_matching_task(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();

        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $character = $factionLoyaltyFactory->getCharacter();
        $assistingNpc = $factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $craftingItem = $factionLoyaltyFactory->getCraftingItemsForNpc($assistingNpc)[0];

        $result = $this->handler->showCraftForNpcButton($character, $craftingItem->type);

        $this->assertTrue($result);
    }

    public function test_show_craft_for_npc_button_is_false_when_no_matching_task(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();

        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $character = $factionLoyaltyFactory->getCharacter();

        $result = $this->handler->showCraftForNpcButton($character, 'not-a-real-crafting-type');

        $this->assertFalse($result);
    }

    public function test_show_craft_for_npc_button_is_false_when_character_has_no_pledged_faction(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();

        $result = $this->handler->showCraftForNpcButton($character, 'weapon');

        $this->assertFalse($result);
    }

    public function test_show_craft_for_npc_button_is_false_when_no_npc_is_currently_helping(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();

        $factionLoyaltyFactory = (new FactionLoyaltyFactory)->setUp($character);
        $character = $factionLoyaltyFactory->getCharacter();
        $assistingNpc = $factionLoyaltyFactory->getAssistingFactionLoyaltyNpc();
        $assistingNpc->update(['currently_helping' => false]);

        $result = $this->handler->showCraftForNpcButton($character, 'weapon');

        $this->assertFalse($result);
    }

    public function test_has_incomplete_tasks_is_false_when_npc_has_no_task_record(): void
    {
        $character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem()->getCharacter();

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
}
