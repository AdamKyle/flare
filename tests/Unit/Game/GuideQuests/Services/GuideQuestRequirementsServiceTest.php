<?php

namespace Tests\Unit\Game\GuideQuests\Services;

use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MapNameValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\ClassRanks\Values\ClassSpecialValue;
use App\Game\Events\Values\EventType;
use App\Game\GuideQuests\Services\GuideQuestRequirementsService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateFactionLoyalty;
use Tests\Traits\CreateGameClassSpecial;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGlobalEventGoal;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class GuideQuestRequirementsServiceTest extends TestCase
{
    use CreateEvent,
        CreateFactionLoyalty,
        CreateGameClassSpecial,
        CreateGameMap,
        CreateGameSkill,
        CreateGlobalEventGoal,
        CreateGuideQuest,
        CreateItem,
        CreateNpc,
        CreateQuest,
        RefreshDatabase;

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

        $guideQuest = $this->createGuideQuest([
            'required_kingdom_building_id' => $character->kingdoms()->first()->buildings()->first()->id,
            'required_kingdom_building_level' => 2,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->requiredKingdomSpecificBuildingLevel($character, $guideQuest)->getFinishedRequirements();

        $this->assertContains('required_kingdom_building_level', $finishedRequirements);
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
            'required_event_goal_participation' => 10,
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
}
