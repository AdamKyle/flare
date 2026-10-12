<?php

namespace Tests\Unit\Game\GuideQuests\Services\Requirements;

use App\Game\ClassRanks\Values\ClassSpecialValue;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\GuideQuests\Services\Requirements\CharacterGuideQuestRequirementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateGameClassSpecial;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;

class CharacterGuideQuestRequirementServiceTest extends TestCase
{
    use CreateFactionLoyalty, CreateGameClassSpecial, CreateGuideQuest, CreateItem, CreateNpc, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterGuideQuestRequirementService $guideQuestRequirementsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem();

        $this->guideQuestRequirementsService = new CharacterGuideQuestRequirementService;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->guideQuestRequirementsService = null;
    }

    public function test_get_level_check()
    {
        $guideQuest = $this->createGuideQuest([
            'required_level' => 1,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_level', $finishedRequirements);
    }

    public function test_required_fame_level_check_when_no_pledged_faction()
    {
        $character = $this->character->getCharacter();

        $npc = $this->createNpc([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $character->factions->first()->id,
            'character_id' => $character->id,
            'is_pledged' => false,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => false,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [],
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_fame_level' => 5,
        ]);

        $character = $character->refresh();

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_fame_level', $finishedRequirements);
    }

    public function test_required_fame_level_check_when_not_assisting_npc()
    {
        $character = $this->character->getCharacter();

        $npc = $this->createNpc([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $character->factions->first()->id,
            'character_id' => $character->id,
            'is_pledged' => true,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 0,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => false,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [],
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_fame_level' => 5,
        ]);

        $character = $character->refresh();

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_fame_level', $finishedRequirements);
    }

    public function test_required_fame_level_chec()
    {
        $character = $this->character->getCharacter();

        $npc = $this->createNpc([
            'game_map_id' => $character->map->game_map_id,
        ]);

        $factionLoyalty = $this->createFactionLoyalty([
            'faction_id' => $character->factions->first()->id,
            'character_id' => $character->id,
            'is_pledged' => true,
        ]);

        $factionNpc = $this->createFactionLoyaltyNpc([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
            'current_level' => 10,
            'max_level' => 25,
            'next_level_fame' => 100,
            'currently_helping' => true,
            'kingdom_item_defence_bonus' => 0.002,
        ]);

        $this->createFactionLoyaltyNpcTask([
            'faction_loyalty_id' => $factionLoyalty->id,
            'faction_loyalty_npc_id' => $factionNpc->id,
            'fame_tasks' => [],
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_fame_level' => 5,
        ]);

        $character = $character->refresh();

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_fame_level', $finishedRequirements);
    }

    public function test_required_speciality_item_is_in_inventory()
    {
        $item = $this->createItem([
            'specialty_type' => ItemSpecialtyType::HELL_FORGED->value,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_specialty_type' => ItemSpecialtyType::HELL_FORGED->value,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_specialty_type', $finishedRequirements);
    }

    public function test_required_speciality_item_is_in_set()
    {
        $item = $this->createItem([
            'specialty_type' => ItemSpecialtyType::HELL_FORGED->value,
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(2)->putItemInSet($item, 1)->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_specialty_type' => ItemSpecialtyType::HELL_FORGED->value,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_specialty_type', $finishedRequirements);
    }

    public function test_guide_quest_does_not_require_holy_stacks()
    {
        $guideQuest = $this->createGuideQuest([
            'required_holy_stacks' => null,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_holy_stacks', $finishedRequirements);
    }

    public function test_guide_quest_does_require_holy_stacks()
    {
        $guideQuest = $this->createGuideQuest([
            'required_holy_stacks' => 1,
        ]);

        $item = $this->createItem([
            'type' => 'weapon',
            'holy_stacks' => 20,
        ]);

        $item->appliedHolyStacks()->create([
            'devouring_darkness_bonus' => 0.10,
            'stat_increase_bonus' => 0.10,
        ]);

        $item = $item->refresh();

        $character = $this->character->inventoryManagement()->giveItem($item, true, 'left-hand')->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_holy_stacks', $finishedRequirements);
    }

    public function test_has_class_rank_equipped()
    {
        $character = $this->character->createClassRanks()->getCharacter();

        $gameClassSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $gameClassSpecial->id,
            'level' => 1,
            'current_xp' => 0,
            'required_xp' => ClassSpecialValue::XP_PER_LEVEL,
            'equipped' => true,
        ]);

        $character = $character->refresh();

        $guideQuest = $this->createGuideQuest([
            'required_class_specials_equipped' => 1,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_class_specials_equipped', $finishedRequirements);
    }

    public function test_has_class_rank_equipped_and_above_required_level()
    {
        $character = $this->character->createClassRanks()->getCharacter();

        $character->classRanks()->first()->update([
            'level' => 10,
        ]);

        $gameClassSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $gameClassSpecial->id,
            'level' => 10,
            'current_xp' => 0,
            'required_xp' => ClassSpecialValue::XP_PER_LEVEL,
            'equipped' => true,
        ]);

        $character = $character->refresh();

        $guideQuest = $this->createGuideQuest([
            'required_class_rank_level' => 5,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_class_rank_level', $finishedRequirements);
    }

    public function test_has_required_currency()
    {
        $character = $this->character->getCharacter();

        $character->update([
            'gold' => 10_000,
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_gold' => 5_000,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_gold', $finishedRequirements);
    }

    public function test_has_required_stats()
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_str' => 1,
            'required_dex' => 1,
            'required_int' => 1,
            'required_dur' => 1,
            'required_chr' => 1,
            'required_agi' => 1,
            'required_focus' => 1,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_str', $finishedRequirements);
        $this->assertContains('required_dex', $finishedRequirements);
        $this->assertContains('required_int', $finishedRequirements);
        $this->assertContains('required_dur', $finishedRequirements);
        $this->assertContains('required_chr', $finishedRequirements);
        $this->assertContains('required_agi', $finishedRequirements);
        $this->assertContains('required_focus', $finishedRequirements);
    }

    public function test_has_required_total_stats()
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_stats' => 1,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_stats', $finishedRequirements);
    }
}
