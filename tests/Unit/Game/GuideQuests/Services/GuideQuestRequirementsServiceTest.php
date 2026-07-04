<?php

namespace Tests\Unit\Game\GuideQuests\Services;

use App\Admin\Services\GuideQuestService as AdminGuideQuestService;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\DelveLog;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MapNameValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\ClassRanks\Values\ClassSpecialValue;
use App\Game\Character\CharacterInventory\Values\AlchemyItemType;
use App\Game\Events\Values\EventType;
use App\Game\GuideQuests\Services\GuideQuestRequirementsService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateDelveAutomation;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateGameClassSpecial;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateInventorySets;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class GuideQuestRequirementsServiceTest extends TestCase
{
    use CreateBatchCrafting, CreateDelveAutomation, CreateEvent, CreateFactionLoyalty, CreateGameClassSpecial, CreateGameMap, CreateGameSkill, CreateGlobalEventGoal, CreateGuideQuest, CreateInventorySets, CreateItem, CreateItemAffix, CreateNpc, CreateQuest, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?GuideQuestRequirementsService $guideQuestRequirementsService;

    private ?Item $item;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem();

        $this->guideQuestRequirementsService = resolve(GuideQuestRequirementsService::class);

        $this->item = $this->createItem(['type' => 'quest']);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->item = null;
        $this->guideQuestRequirementsService = null;
    }

    public function testGetLevelCheck()
    {
        $guideQuest = $this->createGuideQuest([
            'required_level' => 1,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredLevelCheck($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_level', $finishedRequirements);
    }

    public function testFinishedRequirementsAreReset()
    {
        $guideQuest = $this->createGuideQuest([
            'required_level' => 1,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredLevelCheck($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_level', $finishedRequirements);

        $this->guideQuestRequirementsService->resetFinishedRequirements();

        $resetRequirements = $this->guideQuestRequirementsService->getFinishedRequirements();

        $this->assertEmpty($resetRequirements);
    }

    public function testGetRequiredSkillCheck()
    {
        $gameSkill = GameSkill::where('name', 'Accuracy')->first();

        $guideQuest = $this->createGuideQuest([
            'required_skill' => $gameSkill->id,
            'required_skill_level' => 1,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredSkillCheck($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_skill_level', $finishedRequirements);
    }

    public function testRequiredDelvePackSizeReturnsFalseWhenNoDelveRowExists(): void
    {
        $guideQuest = $this->createGuideQuest([
            'required_delve_pack_size' => 5,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredDelvePackSize($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_delve_pack_size', $finishedRequirements);
    }

    public function testRequiredDelvePackSizePassesWithRetainedCompletedDelveRow(): void
    {
        $guideQuest = $this->createGuideQuest([
            'required_delve_pack_size' => 5,
        ]);

        $character = $this->character->getCharacter();

        $delve = $this->createDelveAutomation([
            'character_id' => $character->id,
            'monster_id' => 0,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $this->createDelveAutomationLog([
            'character_id' => $character->id,
            'delve_exploration_id' => $delve->id,
            'pack_size' => 5,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredDelvePackSize($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_delve_pack_size', $finishedRequirements);
    }

    public function testRequiredDelvePackSizeFailsWithInsufficientRetainedCompletedDelveRow(): void
    {
        $guideQuest = $this->createGuideQuest([
            'required_delve_pack_size' => 10,
        ]);

        $character = $this->character->getCharacter();

        $delve = $this->createDelveAutomation([
            'character_id' => $character->id,
            'monster_id' => 0,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $this->createDelveAutomationLog([
            'character_id' => $character->id,
            'delve_exploration_id' => $delve->id,
            'pack_size' => 5,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredDelvePackSize($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_delve_pack_size', $finishedRequirements);
    }

    public function testDelveLogsHasCompositeIndexForLatestPackSizeLookup(): void
    {
        $indexColumns = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'delve_logs')
            ->where('INDEX_NAME', 'delve_logs_character_exploration_created_index')
            ->orderBy('SEQ_IN_INDEX')
            ->pluck('COLUMN_NAME')
            ->all();

        $this->assertSame([
            'character_id',
            'delve_exploration_id',
            'created_at',
        ], $indexColumns);
    }

    public function testRequiredDelvePackSizeUsesLatestDelveLogForCompletedDelveExploration(): void
    {
        $guideQuest = $this->createGuideQuest([
            'required_delve_pack_size' => 10,
        ]);

        $character = $this->character->getCharacter();

        $delve = $this->createDelveAutomation([
            'character_id' => $character->id,
            'monster_id' => 0,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $this->createDelveAutomationLog([
            'character_id' => $character->id,
            'delve_exploration_id' => $delve->id,
            'pack_size' => 5,
            'created_at' => now()->subMinutes(10),
        ]);

        $this->createDelveAutomationLog([
            'character_id' => $character->id,
            'delve_exploration_id' => $delve->id,
            'pack_size' => 10,
            'created_at' => now(),
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredDelvePackSize($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_delve_pack_size', $finishedRequirements);
    }

    public function testGetSecondaryRequiredSkillCheck()
    {
        $gameSkill = GameSkill::where('name', 'Accuracy')->first();

        $guideQuest = $this->createGuideQuest([
            'required_secondary_skill' => $gameSkill->id,
            'required_secondary_skill_level' => 1,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredSkillCheck($character, $guideQuest, false)->getFinishedRequirements();

        $this->assertContains('required_secondary_skill_level', $finishedRequirements);
    }

    public function testGetClassSkillCheck()
    {
        $guideQuest = $this->createGuideQuest([
            'required_skill_type' => SkillTypeValue::EFFECTS_CLASS->value,
            'required_skill_type_level' => 1,
        ]);

        $character = $this->character->assignSkill(
            $this->createGameSkill([
                'type' => SkillTypeValue::EFFECTS_CLASS->value,
                'game_class_id' => $this->character->getCharacterClassId(),
            ]),
            10
        )->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredSkillTypeCheck($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_skill_type_level', $finishedRequirements);
    }

    public function testGetCraftingSkillCheck()
    {
        $guideQuest = $this->createGuideQuest([
            'required_skill_type' => SkillTypeValue::CRAFTING->value,
            'required_skill_type_level' => 1,
        ]);

        $character = $this->character->assignSkill(
            $this->createGameSkill([
                'type' => SkillTypeValue::CRAFTING->value
            ]),
            10
        )->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredSkillTypeCheck($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_skill_type_level', $finishedRequirements);
    }

    public function testLogFailedSkillTypeCheck()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Invalid Skill Type Value for: 999');

        $guideQuest = $this->createGuideQuest([
            'required_skill_type' => 999,
            'required_skill_type_level' => 1,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredSkillTypeCheck($character, $guideQuest)->getFinishedRequirements();

        $this->assertEmpty($finishedRequirements);
    }

    public function testRequiredFactionLevel()
    {
        $gameMap = GameMap::first();

        $guideQuest = $this->createGuideQuest([
            'required_faction_id' => GameMap::first()->id,
            'required_faction_level' => 1,
        ]);

        $character = $this->character->getCharacter();

        $character->factions()->where('game_map_id', $gameMap->id)->update([
            'current_level' => 1,
        ]);

        $character = $character->refresh();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredFactionLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_faction_level', $finishedRequirements);
    }

    public function testRequiredMapAccess()
    {
        $requireditem = $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::LABYRINTH,
        ]);

        $gameMap = $this->createGameMap([
            'name' => MapNameValue::LABYRINTH
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_game_map_id' => $gameMap->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($requireditem)->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredGameMapAccess($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_game_map_id', $finishedRequirements);
    }

    public function testGetRequiredQuest()
    {
        $npc = $this->createNpc([
            'game_map_id' => GameMap::first()->id,
        ]);

        $quest = $this->createQuest([
            'npc_id' => $npc->id,
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_quest_id' => $quest->id,
        ]);

        $character = $this->character->getCharacter();

        $character->questsCompleted()->create([
            'quest_id' => $quest->id,
        ]);

        $character = $character->refresh();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredQuest($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_quest_id', $finishedRequirements);
    }

    public function testGetPrimaryRequiredQuestItem()
    {
        $questItem = $this->createItem([
            'type' => 'quest',
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_quest_item_id' => $questItem->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($questItem)->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredQuestItem($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_quest_item_id', $finishedRequirements);
    }

    public function testGetPrimaryRequiredQuestItemUsedInCompletedQuest()
    {
        $questItem = $this->createItem([
            'type' => 'quest',
        ]);

        $npc = $this->createNpc([
            'game_map_id' => GameMap::first()->id,
        ]);

        $quest = $this->createQuest([
            'npc_id' => $npc->id,
            'item_id' => $questItem->id,
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_quest_item_id' => $questItem->id,
        ]);

        $character = $this->character->getCharacter();

        $character->questsCompleted()->create([
            'quest_id' => $quest->id,
        ]);

        $character = $character->refresh();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredQuestItem($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_quest_item_id', $finishedRequirements);
    }


    public function testGetSecondaryRequiredQuestItem()
    {
        $questItem = $this->createItem([
            'type' => 'quest',
        ]);

        $guideQuest = $this->createGuideQuest([
            'secondary_quest_item_id' => $questItem->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($questItem)->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredQuestItem($character, $guideQuest, false)->getFinishedRequirements();

        $this->assertContains('secondary_quest_item_id', $finishedRequirements);
    }

    public function testRequiredFameLevelCheckWhenNoPledgedFaction()
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

        $finishedRequirements = $this->guideQuestRequirementsService->requiredFameLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_fame_level', $finishedRequirements);
    }

    public function testRequiredFameLevelCheckWhenNotAssistingNPC()
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

        $finishedRequirements = $this->guideQuestRequirementsService->requiredFameLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_fame_level', $finishedRequirements);
    }

    public function testRequiredFameLevelChec()
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

        $finishedRequirements = $this->guideQuestRequirementsService->requiredFameLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_fame_level', $finishedRequirements);
    }

    public function testRequiredSpecialityItemIsInInventory()
    {
        $item = $this->createItem([
            'specialty_type' => ItemSpecialtyType::HELL_FORGED
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_specialty_type' => ItemSpecialtyType::HELL_FORGED,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredSpecialtyType($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_specialty_type', $finishedRequirements);
    }

    public function testRequiredSpecialityItemIsInSet()
    {
        $item = $this->createItem([
            'specialty_type' => ItemSpecialtyType::HELL_FORGED
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(2)->putItemInSet($item, 1)->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_specialty_type' => ItemSpecialtyType::HELL_FORGED,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredSpecialtyType($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_specialty_type', $finishedRequirements);
    }

    public function testGuideQuestDoesNotRequireHolyStacks()
    {
        $guideQuest = $this->createGuideQuest([
            'required_holy_stacks' => null,
        ]);

        $character = $this->character->getCharacter();


        $finishedRequirements = $this->guideQuestRequirementsService->requiredHolyStacks($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_holy_stacks', $finishedRequirements);
    }

    public function testGuideQuestDoesRequireHolyStacks()
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

        $finishedRequirements = $this->guideQuestRequirementsService->requiredHolyStacks($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_holy_stacks', $finishedRequirements);
    }

    public function testFetchRequiredKingdomsCount()
    {
        $character = $this->character->kingdomManagement()->assignKingdom()->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_kingdoms' => 1,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredKingdomCount($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_kingdoms', $finishedRequirements);
    }

    public function testFetchRequiredKingdomGoldBars()
    {
        $character = $this->character->kingdomManagement()->assignKingdom([
            'gold_bars' => 1000
        ])->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_gold_bars' => 10,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredKingdomGoldBarsAmount($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_gold_bars', $finishedRequirements);
    }

    public function testFetchRequiredKingdomBuildingLevel()
    {
        $character = $this->character->kingdomManagement()->assignKingdom()->assignBuilding([], [
            'level' => 5
        ])->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_kingdom_level' => 2,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredKingdomBuildingLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_kingdom_level', $finishedRequirements);
    }

    public function testFetchRequiredSpecificKingdomBuildingLevel()
    {
        $character = $this->character->kingdomManagement()->assignKingdom()->assignBuilding([], [
            'level' => 5
        ])->getCharacter();
        $building = $character->kingdoms()->first()->buildings()->first();

        $guideQuest = $this->createGuideQuest([
            'required_kingdom_building_id' => $building->game_building_id,
            'required_kingdom_building_level' => 2,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredKingdomSpecificBuildingLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_kingdom_building_level', $finishedRequirements);
    }

    public function testStaleRequiredSpecificKingdomBuildingDataDoesNotFatal()
    {
        $character = $this->character->kingdomManagement()->assignKingdom()->assignBuilding([], [
            'level' => 5
        ])->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_kingdom_building_id' => GameBuilding::max('id') + 1,
            'required_kingdom_building_level' => 2,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredKingdomSpecificBuildingLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_kingdom_building_level', $finishedRequirements);
    }

    public function testFetchRequiredKingdomUnitAmount()
    {
        $character = $this->character->kingdomManagement()->assignKingdom()->assignUnits([], 1000)->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_kingdom_units' => 100,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredKingdomUnitCount($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_kingdom_units', $finishedRequirements);
    }

    public function testFetchRequiredKIngdomPassiveSkillLevel()
    {
        $character = $this->character->assignPassiveSkills()->getCharacter();

        $passiveSkill = $character->passiveSkills()->first();

        $passiveSkillId = $passiveSkill->passive_skill_id;

        $passiveSkill->update([
            'current_level' => 5
        ]);

        $character = $character->refresh();

        $guideQuest = $this->createGuideQuest([
            'required_passive_level' => 2,
            'required_passive_skill' => $passiveSkillId,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredKingdomPassiveLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_passive_level', $finishedRequirements);
    }

    public function testHasClassRankEquipped()
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

        $finishedRequirements = $this->guideQuestRequirementsService->requiredClassRanksEquipped($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_class_specials_equipped', $finishedRequirements);
    }

    public function testHasClassRankEquippedAndAboveRequiredLevel()
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

        $finishedRequirements = $this->guideQuestRequirementsService->requiredClassRankLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_class_rank_level', $finishedRequirements);
    }

    public function testHasRequiredCurrency()
    {
        $character = $this->character->getCharacter();

        $character->update([
            'gold' => 10_000
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_gold' => 5_000,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredCurrency($character, $guideQuest, 'gold')->getFinishedRequirements();

        $this->assertContains('required_gold', $finishedRequirements);
    }

    public function testHasRequiredStats()
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_str' => 1,
            'required_dex' => 1,
            'required_int' => 1,
            'required_dur' => 1,
            'required_chr' => 1,
            'required_agi' => 1,
            'required_focus' => 1
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredStats($character, $guideQuest, [
            'str',
            'dex',
            'int',
            'dur',
            'chr',
            'agi',
            'focus'
        ])->getFinishedRequirements();

        $this->assertContains('required_str', $finishedRequirements);
        $this->assertContains('required_dex', $finishedRequirements);
        $this->assertContains('required_int', $finishedRequirements);
        $this->assertContains('required_dur', $finishedRequirements);
        $this->assertContains('required_chr', $finishedRequirements);
        $this->assertContains('required_agi', $finishedRequirements);
        $this->assertContains('required_focus', $finishedRequirements);
    }

    public function testHasRequiredTotalStats()
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_stats' => 1,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredTotalStats($character, $guideQuest, [
            'str',
            'dex',
            'int',
            'dur',
            'chr',
            'agi',
            'focus'
        ])->getFinishedRequirements();

        $this->assertContains('required_stats', $finishedRequirements);
    }

    public function testPlayerMustBeOnSpecificMap()
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'be_on_game_map' => $character->map->game_map_id
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requirePlayerToBeOnASpecificMap($character, $guideQuest)->getFinishedRequirements();;

        $this->assertContains('required_to_be_on_game_map_name', $finishedRequirements);
    }

    public function testPlayerHasGlobalKillAmount()
    {
        $character = $this->character->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
            ])->id,
        ]);

        $character = $character->refresh();

        $this->createEvent([
            'type' => EventType::WINTER_EVENT,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_kills' => 1000,
            'event_type' => EventType::WINTER_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventParticipation([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'current_kills' => 100,
            'current_crafts' => null,
        ]);

        $this->createGlobalEventKill([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'kills' => 100,
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_event_goal_participation' => 10
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredGlobalEventKillAmount($character, $guideQuest)->getFinishedRequirements();;

        $this->assertContains('required_event_goal_participation', $finishedRequirements);
    }

    public function testPlayerDoesNotHaveGlobalKillAmountWhenNoEventRunning()
    {
        $character = $this->character->getCharacter();


        $character = $character->refresh();

        $guideQuest = $this->createGuideQuest([
            'required_event_goal_participation' => 10
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredGlobalEventKillAmount($character, $guideQuest)->getFinishedRequirements();;

        $this->assertNotContains('required_event_goal_participation', $finishedRequirements);
    }

    public function testPlayerHasGlobalEventCraftAmount()
    {
        $character = $this->character->getCharacter();
        $eventGoal = $this->createGlobalEventGoal([
            'max_crafts' => 1000,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventCrafts([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'crafts' => 100,
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_event_goal_crafting_participation' => 10,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredGlobalEventCraftAmount($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_event_goal_crafting_participation', $finishedRequirements);
    }

    public function testPlayerDoesNotHaveGlobalEventCraftAmountWhenNoCraftRowExists()
    {
        $character = $this->character->getCharacter();
        $guideQuest = $this->createGuideQuest([
            'required_event_goal_crafting_participation' => 10,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredGlobalEventCraftAmount($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_event_goal_crafting_participation', $finishedRequirements);
    }

    public function testPlayerDoesNotHaveGlobalEventCraftAmountWhenCraftsAreBelowRequirement()
    {
        $character = $this->character->getCharacter();
        $eventGoal = $this->createGlobalEventGoal([
            'max_crafts' => 1000,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventCrafts([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'crafts' => 5,
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_event_goal_crafting_participation' => 10,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredGlobalEventCraftAmount($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_event_goal_crafting_participation', $finishedRequirements);
    }

    public function testPlayerHasGlobalEventEnchantAmount()
    {
        $character = $this->character->getCharacter();
        $eventGoal = $this->createGlobalEventGoal([
            'max_enchants' => 1000,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventEnchants([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'enchants' => 100,
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_event_goal_enchanting_participation' => 10,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredGlobalEventEnchantAmount($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_event_goal_enchanting_participation', $finishedRequirements);
    }

    public function testPlayerDoesNotHaveGlobalEventEnchantAmountWhenNoEnchantRowExists()
    {
        $character = $this->character->getCharacter();
        $guideQuest = $this->createGuideQuest([
            'required_event_goal_enchanting_participation' => 10,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredGlobalEventEnchantAmount($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_event_goal_enchanting_participation', $finishedRequirements);
    }

    public function testPlayerDoesNotHaveGlobalEventEnchantAmountWhenEnchantsAreBelowRequirement()
    {
        $character = $this->character->getCharacter();
        $eventGoal = $this->createGlobalEventGoal([
            'max_enchants' => 1000,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
            'unique_type' => RandomAffixDetails::LEGENDARY,
        ]);

        $this->createGlobalEventEnchants([
            'global_event_goal_id' => $eventGoal->id,
            'character_id' => $character->id,
            'enchants' => 5,
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_event_goal_enchanting_participation' => 10,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredGlobalEventEnchantAmount($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_event_goal_enchanting_participation', $finishedRequirements);
    }

    public function testRequiredSkillCheckReturnsFalseWhenCharacterDoesNotHaveSkill(): void
    {
        $gameSkill = $this->createGameSkill(['name' => 'NonexistentSkill' . uniqid()]);

        $guideQuest = $this->createGuideQuest([
            'required_skill' => $gameSkill->id,
            'required_skill_level' => 1,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredSkillCheck($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_skill_level', $finishedRequirements);
    }

    public function testRequiredFactionLevelReturnsFalseWhenCharacterHasNoFactionForMap(): void
    {
        $gameMap = $this->createGameMap(['name' => 'UnreachableMap' . uniqid()]);

        $guideQuest = $this->createGuideQuest([
            'required_faction_id' => $gameMap->id,
            'required_faction_level' => 1,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredFactionLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_faction_level', $finishedRequirements);
    }

    public function testRequiredKingdomPassiveLevelReturnsFalseWhenCharacterDoesNotHavePassiveSkill(): void
    {
        $guideQuest = $this->createGuideQuest([
            'required_passive_level' => 1,
            'required_passive_skill' => 999999,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredKingdomPassiveLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_passive_level', $finishedRequirements);
    }

    public function testRunningCraftExperienceBatchAtRequiredHoursPasses(): void
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_batch_crafting_type' => 'craft',
            'required_batch_crafting_hours' => 2,
        ]);

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => 'craft',
            'started_at' => now()->subHours(2),
            'completed_at' => null,
            'cancelled_at' => null,
            'progress' => [
                'craft_mode' => 'experience',
            ],
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftingExperienceHours($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_batch_crafting_hours', $finishedRequirements);
    }

    public function testCompletedCraftAndEnchantExperienceBatchAtRequiredHoursPasses(): void
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_batch_crafting_type' => 'craft_and_enchant',
            'required_batch_crafting_hours' => 3,
        ]);

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => 'craft_and_enchant',
            'started_at' => now()->subHours(4),
            'completed_at' => now()->subHour(),
            'cancelled_at' => null,
            'progress' => [
                'craft_mode' => 'experience',
            ],
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftingExperienceHours($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_batch_crafting_hours', $finishedRequirements);
    }

    public function testCancelledAlchemyExperienceBatchAtRequiredHoursPasses(): void
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_batch_crafting_type' => 'alchemy',
            'required_batch_crafting_hours' => 2,
        ]);

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => 'alchemy',
            'started_at' => now()->subHours(3),
            'completed_at' => now()->subHour(),
            'cancelled_at' => now()->subHour(),
            'progress' => [
                'alchemy_mode' => 'experience',
            ],
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftingExperienceHours($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_batch_crafting_hours', $finishedRequirements);
    }

    public function testCancelledMatchingBatchBeforeRequiredHoursFails(): void
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_batch_crafting_type' => 'alchemy',
            'required_batch_crafting_hours' => 2,
        ]);

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => 'alchemy',
            'started_at' => now()->subMinutes(90),
            'completed_at' => now(),
            'cancelled_at' => now(),
            'progress' => [
                'alchemy_mode' => 'experience',
            ],
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftingExperienceHours($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_batch_crafting_hours', $finishedRequirements);
    }

    public function testMatchingTypeWithNonExperienceModeFails(): void
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_batch_crafting_type' => 'craft',
            'required_batch_crafting_hours' => 2,
        ]);

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => 'craft',
            'started_at' => now()->subHours(3),
            'completed_at' => null,
            'cancelled_at' => null,
            'progress' => [
                'craft_mode' => 'gold',
            ],
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftingExperienceHours($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_batch_crafting_hours', $finishedRequirements);
    }

    public function testWrongBatchTypeFails(): void
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_batch_crafting_type' => 'trinketry',
            'required_batch_crafting_hours' => 2,
        ]);

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => 'alchemy',
            'started_at' => now()->subHours(3),
            'completed_at' => null,
            'cancelled_at' => null,
            'progress' => [
                'alchemy_mode' => 'experience',
            ],
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftingExperienceHours($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_batch_crafting_hours', $finishedRequirements);
    }

    public function testHolyOilsDoesNotSatisfyTheRequirement(): void
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_batch_crafting_type' => 'alchemy',
            'required_batch_crafting_hours' => 2,
        ]);

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => 'holy_oils',
            'started_at' => now()->subHours(3),
            'completed_at' => null,
            'cancelled_at' => null,
            'progress' => [
                'alchemy_mode' => 'experience',
            ],
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftingExperienceHours($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_batch_crafting_hours', $finishedRequirements);
    }

    public function testTrinketryExperienceBatchAtRequiredHoursPasses(): void
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_batch_crafting_type' => 'trinketry',
            'required_batch_crafting_hours' => 2,
        ]);

        $this->createBatchCrafting([
            'character_id' => $character->id,
            'user_id' => $character->user_id,
            'batch_type' => 'trinketry',
            'started_at' => now()->subHours(3),
            'completed_at' => null,
            'cancelled_at' => null,
            'progress' => [
                'trinketry_mode' => 'experience',
            ],
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftingExperienceHours($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_batch_crafting_hours', $finishedRequirements);
    }

    public function testPlainDaggerInventoryRequirementPassesWithEnoughUnenchantedMatchingItems(): void
    {
        $character = $this->character->getCharacter();
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $dagger->id, 'amount' => 2, 'must_be_enchanted' => false],
            ],
        ]);

        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);
        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testPlainDaggerInventoryRequirementFailsWithInsufficientAmount(): void
    {
        $character = $this->character->getCharacter();
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $dagger->id, 'amount' => 2, 'must_be_enchanted' => false],
            ],
        ]);

        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testPlainDaggerInventoryRequirementDoesNotCountEnchantedDaggers(): void
    {
        $character = $this->character->getCharacter();
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $enchantedDagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'parent_id' => $dagger->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => null]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
            ],
        ]);

        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $enchantedDagger->id]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testEnchantedHelmetInventoryRequirementPassesWhenEnoughMatchingEnchantedHelmetsExist(): void
    {
        $character = $this->character->getCharacter();
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $suffix = $this->createItemAffix(['type' => 'suffix']);
        $helmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $enchantedHelmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'parent_id' => $helmet->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => $suffix->id]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $helmet->id, 'amount' => 2, 'must_be_enchanted' => true],
            ],
        ]);

        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $enchantedHelmet->id]);
        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $enchantedHelmet->id]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testEnchantedHelmetInventoryRequirementFailsWhenMatchingItemsHaveOnlyPrefix(): void
    {
        $character = $this->character->getCharacter();
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $helmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $prefixedHelmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'parent_id' => $helmet->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => null]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $helmet->id, 'amount' => 1, 'must_be_enchanted' => true],
            ],
        ]);

        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $prefixedHelmet->id]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testEnchantedHelmetInventoryRequirementFailsWhenMatchingItemsHaveOnlySuffix(): void
    {
        $character = $this->character->getCharacter();
        $suffix = $this->createItemAffix(['type' => 'suffix']);
        $helmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $suffixedHelmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'parent_id' => $helmet->id, 'item_prefix_id' => null, 'item_suffix_id' => $suffix->id]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $helmet->id, 'amount' => 1, 'must_be_enchanted' => true],
            ],
        ]);

        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $suffixedHelmet->id]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testEnchantedHelmetInventoryRequirementDoesNotCareWhichPrefixOrSuffixIsApplied(): void
    {
        $character = $this->character->getCharacter();
        $prefix = $this->createItemAffix(['type' => 'prefix', 'name' => 'Sharp']);
        $suffix = $this->createItemAffix(['type' => 'suffix', 'name' => 'Protection']);
        $otherPrefix = $this->createItemAffix(['type' => 'prefix', 'name' => 'Guarding']);
        $otherSuffix = $this->createItemAffix(['type' => 'suffix', 'name' => 'Power']);
        $helmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $firstEnchantedHelmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'parent_id' => $helmet->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => $suffix->id]);
        $secondEnchantedHelmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'parent_id' => $firstEnchantedHelmet->id, 'item_prefix_id' => $otherPrefix->id, 'item_suffix_id' => $otherSuffix->id]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $helmet->id, 'amount' => 2, 'must_be_enchanted' => true],
            ],
        ]);

        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $firstEnchantedHelmet->id]);
        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $secondEnchantedHelmet->id]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testEnchantedHelmetInventoryRequirementDoesNotCountUnenchantedHelmets(): void
    {
        $character = $this->character->getCharacter();
        $helmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $helmet->id, 'amount' => 1, 'must_be_enchanted' => true],
            ],
        ]);

        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $helmet->id]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testPlainDaggerInventoryRequirementFailsWhenMatchingItemsAreEnchantedWithBothPrefixAndSuffix(): void
    {
        $character = $this->character->getCharacter();
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $suffix = $this->createItemAffix(['type' => 'suffix']);
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $enchantedDagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'parent_id' => $dagger->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => $suffix->id]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
            ],
        ]);

        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $enchantedDagger->id]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testPlayerFacingRequirementDataIncludesItemNameTypeAmountAndEnchantedState(): void
    {
        $helmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $helmet->id, 'amount' => 10, 'must_be_enchanted' => true],
            ],
        ]);

        $displayRows = $guideQuest->required_batch_crafted_item_names;

        $this->assertSame('Iron Helmet', $displayRows[0]['name']);
        $this->assertSame('helmet', $displayRows[0]['type']);
        $this->assertSame('Helmet', $displayRows[0]['type_name']);
        $this->assertSame(10, $displayRows[0]['amount']);
        $this->assertTrue($displayRows[0]['must_be_enchanted']);
    }

    public function testMultipleConfiguredItemRowsPassOnlyWhenAllRowsAreSatisfied(): void
    {
        $character = $this->character->getCharacter();
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $suffix = $this->createItemAffix(['type' => 'suffix']);
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $helmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $enchantedHelmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'parent_id' => $helmet->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => $suffix->id]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
                ['item_id' => $helmet->id, 'amount' => 1, 'must_be_enchanted' => true],
            ],
        ]);

        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);
        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $enchantedHelmet->id]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testMultipleConfiguredItemRowsFailWhenOneRowIsMissing(): void
    {
        $character = $this->character->getCharacter();
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $helmet = $this->createItem(['name' => 'Iron Helmet', 'type' => 'helmet', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
                ['item_id' => $helmet->id, 'amount' => 1, 'must_be_enchanted' => true],
            ],
        ]);

        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testNormalInventoryIsCheckedNotCraftedItemsSet(): void
    {
        $character = $this->character->getCharacter();
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $inventorySet = $this->createInventorySet(['character_id' => $character->id, 'special_type' => 'batch_crafting']);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
            ],
        ]);

        $this->createInventorySetSlot(['inventory_set_id' => $inventorySet->id, 'item_id' => $dagger->id]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testAlchemyBagRequirementPassesWhenTheBagHasEnoughAmount(): void
    {
        $character = $this->character->getCharacter();
        $potion = $this->createItem(['name' => 'Lesser Stat Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'alchemy_bag', 'item_id' => $potion->id, 'amount' => 5, 'must_be_enchanted' => false],
            ],
        ]);

        $character->alchemyBag->slots()->create(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $potion->id, 'amount' => 5]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testAlchemyBagRequirementFailsWhenTheBagHasInsufficientAmount(): void
    {
        $character = $this->character->getCharacter();
        $potion = $this->createItem(['name' => 'Lesser Stat Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'alchemy_bag', 'item_id' => $potion->id, 'amount' => 5, 'must_be_enchanted' => false],
            ],
        ]);

        $character->alchemyBag->slots()->create(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $potion->id, 'amount' => 4]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testAlchemyBagRequirementDoesNotCountNormalInventory(): void
    {
        $character = $this->character->getCharacter();
        $potion = $this->createItem(['name' => 'Lesser Stat Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'alchemy_bag', 'item_id' => $potion->id, 'amount' => 1, 'must_be_enchanted' => false],
            ],
        ]);

        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $potion->id]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testInventoryRequirementDoesNotCountAlchemyBag(): void
    {
        $character = $this->character->getCharacter();
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'inventory', 'item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
            ],
        ]);

        $character->alchemyBag->slots()->create(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $dagger->id, 'amount' => 1]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testMixedInventoryPlusAlchemyRequirementsPassWhenBothRowsAreSatisfied(): void
    {
        $character = $this->character->getCharacter();
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $potion = $this->createItem(['name' => 'Lesser Stat Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'inventory', 'item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
                ['source' => 'alchemy_bag', 'item_id' => $potion->id, 'amount' => 3, 'must_be_enchanted' => false],
            ],
        ]);

        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);
        $character->alchemyBag->slots()->create(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $potion->id, 'amount' => 3]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testMixedInventoryPlusAlchemyRequirementsFailWhenTheAlchemyRowIsShort(): void
    {
        $character = $this->character->getCharacter();
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $potion = $this->createItem(['name' => 'Lesser Stat Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);
        $guideQuest = $this->createGuideQuest([
            'required_batch_crafted_items' => [
                ['source' => 'inventory', 'item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
                ['source' => 'alchemy_bag', 'item_id' => $potion->id, 'amount' => 3, 'must_be_enchanted' => false],
            ],
        ]);

        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);
        $character->alchemyBag->slots()->create(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $potion->id, 'amount' => 2]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredBatchCraftedItems($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_batch_crafted_items', $finishedRequirements);
    }

    public function testOnlyTwoConfiguredRowsAreHonoredAfterCleaning(): void
    {
        $dagger = $this->createItem(['name' => 'Clean Iron Dagger', 'type' => 'dagger', 'can_craft' => true]);
        $helmet = $this->createItem(['name' => 'Clean Iron Helmet', 'type' => 'helmet', 'can_craft' => true]);
        $ring = $this->createItem(['name' => 'Clean Copper Ring', 'type' => 'ring', 'can_craft' => true]);
        $guideQuestService = new AdminGuideQuestService();

        $params = $guideQuestService->cleanRequest([
            'required_batch_crafting_type' => null,
            'required_batch_crafting_hours' => null,
            'required_batch_crafted_items' => [
                ['source' => 'inventory', 'item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
                ['source' => 'inventory', 'item_id' => $helmet->id, 'amount' => 1, 'must_be_enchanted' => false],
                ['source' => 'inventory', 'item_id' => $ring->id, 'amount' => 1, 'must_be_enchanted' => false],
            ],
            'required_skill_level' => null,
            'required_skill' => null,
            'required_passive_level' => null,
            'required_passive_skill' => null,
            'required_faction_level' => null,
            'required_faction_id' => null,
        ]);

        $this->assertCount(2, $params['required_batch_crafted_items']);
        $this->assertSame($dagger->id, $params['required_batch_crafted_items'][0]['item_id']);
        $this->assertSame($helmet->id, $params['required_batch_crafted_items'][1]['item_id']);
    }
}
