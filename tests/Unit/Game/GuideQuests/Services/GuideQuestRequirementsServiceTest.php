<?php

namespace Tests\Unit\Game\GuideQuests\Services;

use App\Admin\Services\GuideQuestService as AdminGuideQuestService;
use App\Flare\Items\Values\AlchemyItemType;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MapNameValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\ClassRanks\Values\ClassSpecialValue;
use App\Game\Events\Values\EventType;
use App\Game\Events\Values\ScheduledEventStatus;
use App\Game\GuideQuests\Services\GuideQuestRequirementsService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateBatchCrafting;
use Tests\Traits\CreateDelveAutomation;
use Tests\Traits\CreateEvent;
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
use Tests\Traits\CreateScheduledEvent;

class GuideQuestRequirementsServiceTest extends TestCase
{
    use CreateBatchCrafting, CreateDelveAutomation, CreateEvent, CreateFactionLoyalty, CreateGameClassSpecial, CreateGameMap, CreateGameSkill, CreateGlobalEventGoal, CreateGuideQuest, CreateInventorySets, CreateItem, CreateItemAffix, CreateNpc, CreateQuest, CreateScheduledEvent, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?GuideQuestRequirementsService $guideQuestRequirementsService;

    private ?Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem();

        $this->guideQuestRequirementsService = resolve(GuideQuestRequirementsService::class);

        $this->item = $this->createItem(['type' => 'quest']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->item = null;
        $this->guideQuestRequirementsService = null;
    }

    public function test_get_level_check()
    {
        $guideQuest = $this->createGuideQuest([
            'required_level' => 1,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredLevelCheck($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_level', $finishedRequirements);
    }

    public function test_finished_requirements_are_reset()
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

    public function test_get_required_skill_check()
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

    public function test_required_delve_pack_size_returns_false_when_no_delve_row_exists(): void
    {
        $guideQuest = $this->createGuideQuest([
            'required_delve_pack_size' => 5,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredDelvePackSize($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_delve_pack_size', $finishedRequirements);
    }

    public function test_required_delve_pack_size_passes_with_retained_completed_delve_row(): void
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

    public function test_required_delve_pack_size_fails_with_insufficient_retained_completed_delve_row(): void
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

    public function test_delve_logs_has_composite_index_for_latest_pack_size_lookup(): void
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

    public function test_required_delve_pack_size_uses_latest_delve_log_for_completed_delve_exploration(): void
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

    public function test_get_secondary_required_skill_check()
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

    public function test_get_class_skill_check()
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

    public function test_get_crafting_skill_check()
    {
        $guideQuest = $this->createGuideQuest([
            'required_skill_type' => SkillTypeValue::CRAFTING->value,
            'required_skill_type_level' => 1,
        ]);

        $character = $this->character->assignSkill(
            $this->createGameSkill([
                'type' => SkillTypeValue::CRAFTING->value,
            ]),
            10
        )->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredSkillTypeCheck($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_skill_type_level', $finishedRequirements);
    }

    public function test_log_failed_skill_type_check()
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

    public function test_required_faction_level()
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

    public function test_required_map_access()
    {
        $requireditem = $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectsValue::LABYRINTH,
        ]);

        $gameMap = $this->createGameMap([
            'name' => MapNameValue::LABYRINTH,
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_game_map_id' => $gameMap->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($requireditem)->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredGameMapAccess($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_game_map_id', $finishedRequirements);
    }

    public function test_get_required_quest()
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

    public function test_get_primary_required_quest_item()
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

    public function test_get_primary_required_quest_item_used_in_completed_quest()
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

    public function test_get_secondary_required_quest_item()
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

        $finishedRequirements = $this->guideQuestRequirementsService->requiredFameLevel($character, $guideQuest)->getFinishedRequirements();

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

        $finishedRequirements = $this->guideQuestRequirementsService->requiredFameLevel($character, $guideQuest)->getFinishedRequirements();

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

        $finishedRequirements = $this->guideQuestRequirementsService->requiredFameLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_fame_level', $finishedRequirements);
    }

    public function test_required_speciality_item_is_in_inventory()
    {
        $item = $this->createItem([
            'specialty_type' => ItemSpecialtyType::HELL_FORGED,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($item)->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_specialty_type' => ItemSpecialtyType::HELL_FORGED,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredSpecialtyType($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_specialty_type', $finishedRequirements);
    }

    public function test_required_speciality_item_is_in_set()
    {
        $item = $this->createItem([
            'specialty_type' => ItemSpecialtyType::HELL_FORGED,
        ]);

        $character = $this->character->inventorySetManagement()->createInventorySets(2)->putItemInSet($item, 1)->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_specialty_type' => ItemSpecialtyType::HELL_FORGED,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredSpecialtyType($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_specialty_type', $finishedRequirements);
    }

    public function test_guide_quest_does_not_require_holy_stacks()
    {
        $guideQuest = $this->createGuideQuest([
            'required_holy_stacks' => null,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredHolyStacks($character, $guideQuest)->getFinishedRequirements();

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

        $finishedRequirements = $this->guideQuestRequirementsService->requiredHolyStacks($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_holy_stacks', $finishedRequirements);
    }

    public function test_fetch_required_kingdoms_count()
    {
        $character = $this->character->kingdomManagement()->assignKingdom()->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_kingdoms' => 1,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredKingdomCount($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_kingdoms', $finishedRequirements);
    }

    public function test_fetch_required_kingdom_gold_bars()
    {
        $character = $this->character->kingdomManagement()->assignKingdom([
            'gold_bars' => 1000,
        ])->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_gold_bars' => 10,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredKingdomGoldBarsAmount($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_gold_bars', $finishedRequirements);
    }

    public function test_fetch_required_kingdom_building_level()
    {
        $character = $this->character->kingdomManagement()->assignKingdom()->assignBuilding([], [
            'level' => 5,
        ])->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_kingdom_level' => 2,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredKingdomBuildingLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_kingdom_level', $finishedRequirements);
    }

    public function test_fetch_required_specific_kingdom_building_level()
    {
        $character = $this->character->kingdomManagement()->assignKingdom()->assignBuilding([], [
            'level' => 5,
        ])->getCharacter();
        $building = $character->kingdoms()->first()->buildings()->first();

        $guideQuest = $this->createGuideQuest([
            'required_kingdom_building_id' => $building->game_building_id,
            'required_kingdom_building_level' => 2,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredKingdomSpecificBuildingLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_kingdom_building_level', $finishedRequirements);
    }

    public function test_stale_required_specific_kingdom_building_data_does_not_fatal()
    {
        $character = $this->character->kingdomManagement()->assignKingdom()->assignBuilding([], [
            'level' => 5,
        ])->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_kingdom_building_id' => GameBuilding::max('id') + 1,
            'required_kingdom_building_level' => 2,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredKingdomSpecificBuildingLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_kingdom_building_level', $finishedRequirements);
    }

    public function test_fetch_required_kingdom_unit_amount()
    {
        $character = $this->character->kingdomManagement()->assignKingdom()->assignUnits([], 1000)->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_kingdom_units' => 100,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredKingdomUnitCount($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_kingdom_units', $finishedRequirements);
    }

    public function test_fetch_required_k_ingdom_passive_skill_level()
    {
        $character = $this->character->assignPassiveSkills()->getCharacter();

        $passiveSkill = $character->passiveSkills()->first();

        $passiveSkillId = $passiveSkill->passive_skill_id;

        $passiveSkill->update([
            'current_level' => 5,
        ]);

        $character = $character->refresh();

        $guideQuest = $this->createGuideQuest([
            'required_passive_level' => 2,
            'required_passive_skill' => $passiveSkillId,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredKingdomPassiveLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_passive_level', $finishedRequirements);
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

        $finishedRequirements = $this->guideQuestRequirementsService->requiredClassRanksEquipped($character, $guideQuest)->getFinishedRequirements();

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

        $finishedRequirements = $this->guideQuestRequirementsService->requiredClassRankLevel($character, $guideQuest)->getFinishedRequirements();

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

        $finishedRequirements = $this->guideQuestRequirementsService->requiredCurrency($character, $guideQuest, 'gold')->getFinishedRequirements();

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

        $finishedRequirements = $this->guideQuestRequirementsService->requiredStats($character, $guideQuest, [
            'str',
            'dex',
            'int',
            'dur',
            'chr',
            'agi',
            'focus',
        ])->getFinishedRequirements();

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

        $finishedRequirements = $this->guideQuestRequirementsService->requiredTotalStats($character, $guideQuest, [
            'str',
            'dex',
            'int',
            'dur',
            'chr',
            'agi',
            'focus',
        ])->getFinishedRequirements();

        $this->assertContains('required_stats', $finishedRequirements);
    }

    public function test_player_must_be_on_specific_map()
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'be_on_game_map' => $character->map->game_map_id,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requirePlayerToBeOnASpecificMap($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_to_be_on_game_map_name', $finishedRequirements);
    }

    public function test_player_has_global_kill_amount()
    {
        $character = $this->character->getCharacter();

        $character->map()->update([
            'game_map_id' => $this->createGameMap([
                'name' => MapNameValue::ICE_PLANE,
            ])->id,
        ]);

        $character = $character->refresh();

        $schedule = $this->createScheduledEvent(['event_type' => EventType::WINTER_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent([
            'type' => EventType::WINTER_EVENT,
            'scheduled_event_id' => $schedule->id,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_kills' => 1000,
            'event_type' => EventType::WINTER_EVENT,
            'event_id' => $event->id,
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
            'required_event_goal_participation' => 10,
            'only_during_event' => EventType::WINTER_EVENT,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredGlobalEventKillAmount($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_event_goal_participation', $finishedRequirements);
    }

    public function test_player_does_not_have_global_kill_amount_when_no_event_running()
    {
        $character = $this->character->getCharacter();

        $character = $character->refresh();

        $guideQuest = $this->createGuideQuest([
            'required_event_goal_participation' => 10,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredGlobalEventKillAmount($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_event_goal_participation', $finishedRequirements);
    }

    public function test_player_has_global_event_craft_amount()
    {
        $character = $this->character->getCharacter();

        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_crafts' => 1000,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
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
            'only_during_event' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredGlobalEventCraftAmount($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_event_goal_crafting_participation', $finishedRequirements);
    }

    public function test_player_does_not_have_global_event_craft_amount_when_no_craft_row_exists()
    {
        $character = $this->character->getCharacter();
        $guideQuest = $this->createGuideQuest([
            'required_event_goal_crafting_participation' => 10,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredGlobalEventCraftAmount($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_event_goal_crafting_participation', $finishedRequirements);
    }

    public function test_player_does_not_have_global_event_craft_amount_when_crafts_are_below_requirement()
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

    public function test_player_has_global_event_enchant_amount()
    {
        $character = $this->character->getCharacter();

        $schedule = $this->createScheduledEvent(['event_type' => EventType::DELUSIONAL_MEMORIES_EVENT, 'status' => ScheduledEventStatus::RUNNING, 'currently_running' => true]);
        $event = $this->createEvent([
            'type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'scheduled_event_id' => $schedule->id,
        ]);

        $eventGoal = $this->createGlobalEventGoal([
            'max_enchants' => 1000,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'event_id' => $event->id,
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
            'only_during_event' => EventType::DELUSIONAL_MEMORIES_EVENT,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredGlobalEventEnchantAmount($character->refresh(), $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_event_goal_enchanting_participation', $finishedRequirements);
    }

    public function test_player_does_not_have_global_event_enchant_amount_when_no_enchant_row_exists()
    {
        $character = $this->character->getCharacter();
        $guideQuest = $this->createGuideQuest([
            'required_event_goal_enchanting_participation' => 10,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredGlobalEventEnchantAmount($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_event_goal_enchanting_participation', $finishedRequirements);
    }

    public function test_player_does_not_have_global_event_enchant_amount_when_enchants_are_below_requirement()
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

    public function test_required_skill_check_returns_false_when_character_does_not_have_skill(): void
    {
        $gameSkill = $this->createGameSkill(['name' => 'NonexistentSkill'.uniqid()]);

        $guideQuest = $this->createGuideQuest([
            'required_skill' => $gameSkill->id,
            'required_skill_level' => 1,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredSkillCheck($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_skill_level', $finishedRequirements);
    }

    public function test_required_faction_level_returns_false_when_character_has_no_faction_for_map(): void
    {
        $gameMap = $this->createGameMap(['name' => 'UnreachableMap'.uniqid()]);

        $guideQuest = $this->createGuideQuest([
            'required_faction_id' => $gameMap->id,
            'required_faction_level' => 1,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredFactionLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_faction_level', $finishedRequirements);
    }

    public function test_required_kingdom_passive_level_returns_false_when_character_does_not_have_passive_skill(): void
    {
        $guideQuest = $this->createGuideQuest([
            'required_passive_level' => 1,
            'required_passive_skill' => 999999,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->requiredKingdomPassiveLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertNotContains('required_passive_level', $finishedRequirements);
    }

    public function test_running_craft_experience_batch_at_required_hours_passes(): void
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

    public function test_completed_craft_and_enchant_experience_batch_at_required_hours_passes(): void
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

    public function test_cancelled_alchemy_experience_batch_at_required_hours_passes(): void
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

    public function test_cancelled_matching_batch_before_required_hours_fails(): void
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

    public function test_matching_type_with_non_experience_mode_fails(): void
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

    public function test_wrong_batch_type_fails(): void
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

    public function test_holy_oils_does_not_satisfy_the_requirement(): void
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

    public function test_trinketry_experience_batch_at_required_hours_passes(): void
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

    public function test_plain_dagger_inventory_requirement_passes_with_enough_unenchanted_matching_items(): void
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

    public function test_plain_dagger_inventory_requirement_fails_with_insufficient_amount(): void
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

    public function test_plain_dagger_inventory_requirement_does_not_count_enchanted_daggers(): void
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

    public function test_enchanted_helmet_inventory_requirement_passes_when_enough_matching_enchanted_helmets_exist(): void
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

    public function test_enchanted_helmet_inventory_requirement_fails_when_matching_items_have_only_prefix(): void
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

    public function test_enchanted_helmet_inventory_requirement_fails_when_matching_items_have_only_suffix(): void
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

    public function test_enchanted_helmet_inventory_requirement_does_not_care_which_prefix_or_suffix_is_applied(): void
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

    public function test_enchanted_helmet_inventory_requirement_does_not_count_unenchanted_helmets(): void
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

    public function test_plain_dagger_inventory_requirement_fails_when_matching_items_are_enchanted_with_both_prefix_and_suffix(): void
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

    public function test_player_facing_requirement_data_includes_item_name_type_amount_and_enchanted_state(): void
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

    public function test_configured_inventory_rows_return_independent_completion_statuses_and_factual_amounts(): void
    {
        $character = $this->character->getCharacter();
        $prefix = $this->createItemAffix(['type' => 'prefix']);
        $suffix = $this->createItemAffix(['type' => 'suffix']);
        $mace = $this->createItem(['name' => 'Diamond Mace', 'type' => 'mace', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $chest = $this->createItem(['name' => "Paladin's Oath Chest", 'type' => 'body', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $enchantedMace = $this->createItem(['name' => 'Diamond Mace', 'type' => 'mace', 'parent_id' => $mace->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => $suffix->id]);
        $enchantedChest = $this->createItem(['name' => "Paladin's Oath Chest", 'type' => 'body', 'parent_id' => $chest->id, 'item_prefix_id' => $prefix->id, 'item_suffix_id' => $suffix->id]);

        for ($slotIndex = 0; $slotIndex < 24; $slotIndex++) {
            $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $enchantedMace->id]);
        }

        for ($slotIndex = 0; $slotIndex < 14; $slotIndex++) {
            $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $enchantedChest->id]);
        }

        $requirements = $this->guideQuestRequirementsService->batchCraftedItemRequirements($character->refresh(), [
            ['source' => 'inventory', 'item_id' => $mace->id, 'amount' => 20, 'must_be_enchanted' => true],
            ['source' => 'inventory', 'item_id' => $chest->id, 'amount' => 15, 'must_be_enchanted' => true],
        ]);

        $this->assertCount(2, $requirements);
        $this->assertSame(0, $requirements[0]['requirement_index']);
        $this->assertSame(24, $requirements[0]['current_amount']);
        $this->assertTrue($requirements[0]['is_complete']);
        $this->assertSame(1, $requirements[1]['requirement_index']);
        $this->assertSame(14, $requirements[1]['current_amount']);
        $this->assertFalse($requirements[1]['is_complete']);
    }

    public function test_missing_configured_item_keeps_an_incomplete_status_beside_a_completed_valid_status(): void
    {
        $character = $this->character->getCharacter();
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);

        $requirements = $this->guideQuestRequirementsService->batchCraftedItemRequirements($character->refresh(), [
            ['source' => 'inventory', 'item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
            ['source' => 'inventory', 'item_id' => 999999, 'amount' => 1, 'must_be_enchanted' => true],
        ]);

        $this->assertTrue($requirements[0]['is_complete']);
        $this->assertSame(999999, $requirements[1]['item_id']);
        $this->assertSame(0, $requirements[1]['current_amount']);
        $this->assertFalse($requirements[1]['is_complete']);
    }

    public function test_mixed_inventory_and_alchemy_rows_return_independent_statuses(): void
    {
        $character = $this->character->getCharacter();
        $dagger = $this->createItem(['name' => 'Iron Dagger', 'type' => 'dagger', 'can_craft' => true, 'item_prefix_id' => null, 'item_suffix_id' => null]);
        $potion = $this->createItem(['name' => 'Lesser Stat Potion', 'type' => 'alchemy', 'alchemy_type' => AlchemyItemType::INCREASE_STATS->value]);
        $character->inventory->slots()->create(['inventory_id' => $character->inventory->id, 'item_id' => $dagger->id]);
        $character->alchemyBag->slots()->create(['alchemy_bag_id' => $character->alchemyBag->id, 'character_id' => $character->id, 'item_id' => $potion->id, 'amount' => 3]);

        $requirements = $this->guideQuestRequirementsService->batchCraftedItemRequirements($character->refresh(), [
            ['source' => 'inventory', 'item_id' => $dagger->id, 'amount' => 1, 'must_be_enchanted' => false],
            ['source' => 'alchemy_bag', 'item_id' => $potion->id, 'amount' => 5, 'must_be_enchanted' => true],
        ]);

        $this->assertSame(1, $requirements[0]['current_amount']);
        $this->assertTrue($requirements[0]['is_complete']);
        $this->assertSame(3, $requirements[1]['current_amount']);
        $this->assertFalse($requirements[1]['must_be_enchanted']);
        $this->assertFalse($requirements[1]['is_complete']);
    }

    public function test_multiple_configured_item_rows_pass_only_when_all_rows_are_satisfied(): void
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

    public function test_multiple_configured_item_rows_fail_when_one_row_is_missing(): void
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

    public function test_normal_inventory_is_checked_not_crafted_items_set(): void
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

    public function test_alchemy_bag_requirement_passes_when_the_bag_has_enough_amount(): void
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

    public function test_alchemy_bag_requirement_fails_when_the_bag_has_insufficient_amount(): void
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

    public function test_alchemy_bag_requirement_does_not_count_normal_inventory(): void
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

    public function test_inventory_requirement_does_not_count_alchemy_bag(): void
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

    public function test_mixed_inventory_plus_alchemy_requirements_pass_when_both_rows_are_satisfied(): void
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

    public function test_mixed_inventory_plus_alchemy_requirements_fail_when_the_alchemy_row_is_short(): void
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

    public function test_only_two_configured_rows_are_honored_after_cleaning(): void
    {
        $dagger = $this->createItem(['name' => 'Clean Iron Dagger', 'type' => 'dagger', 'can_craft' => true]);
        $helmet = $this->createItem(['name' => 'Clean Iron Helmet', 'type' => 'helmet', 'can_craft' => true]);
        $ring = $this->createItem(['name' => 'Clean Copper Ring', 'type' => 'ring', 'can_craft' => true]);
        $guideQuestService = resolve(AdminGuideQuestService::class);

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
