<?php

namespace Tests\Unit\Game\GuideQuests\Services\Requirements;

use App\Flare\Models\GameMap;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\GuideQuests\Services\Requirements\WorldGuideQuestRequirementService;
use App\Game\Maps\Values\MapName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class WorldGuideQuestRequirementServiceTest extends TestCase
{
    use CreateGameMap, CreateGuideQuest, CreateItem, CreateNpc, CreateQuest, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?WorldGuideQuestRequirementService $guideQuestRequirementsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem();

        $this->guideQuestRequirementsService = new WorldGuideQuestRequirementService;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->guideQuestRequirementsService = null;
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

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_faction_level', $finishedRequirements);
    }

    public function test_factions_guide_quest_requires_surface_level_three(): void
    {
        $surfaceGameMap = GameMap::where('name', MapName::SURFACE->value)->firstOrFail();
        $character = $this->character->getCharacter();
        $surfaceFaction = $character->factions()
            ->where('game_map_id', $surfaceGameMap->id)
            ->firstOrFail();
        $guideQuest = $this->createGuideQuest([
            'name' => 'Factions',
            'required_faction_id' => $surfaceGameMap->id,
            'required_faction_level' => 3,
            'faction_points_per_kill' => 25,
        ]);

        $surfaceFaction->update([
            'current_level' => 2,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_faction_level', $finishedRequirements);

        $surfaceFaction->update([
            'current_level' => 3,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_faction_level', $finishedRequirements);
    }

    public function test_required_map_access()
    {
        $requireditem = $this->createItem([
            'type' => 'quest',
            'effect' => ItemEffectType::LABYRINTH->value,
        ]);

        $gameMap = $this->createGameMap([
            'name' => MapName::LABYRINTH->value,
        ]);

        $guideQuest = $this->createGuideQuest([
            'required_game_map_id' => $gameMap->id,
        ]);

        $character = $this->character->inventoryManagement()->giveItem($requireditem)->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

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

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

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

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

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

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

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

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('secondary_quest_item_id', $finishedRequirements);
    }

    public function test_player_must_be_on_specific_map()
    {
        $character = $this->character->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'be_on_game_map' => $character->map->game_map_id,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_to_be_on_game_map_name', $finishedRequirements);
    }

    public function test_required_faction_level_returns_false_when_character_has_no_faction_for_map(): void
    {
        $gameMap = $this->createGameMap(['name' => 'UnreachableMap'.uniqid()]);

        $guideQuest = $this->createGuideQuest([
            'required_faction_id' => $gameMap->id,
            'required_faction_level' => 1,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_faction_level', $finishedRequirements);
    }
}
