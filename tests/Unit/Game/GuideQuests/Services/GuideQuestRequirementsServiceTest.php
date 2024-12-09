<?php

namespace Tests\Unit\Game\GuideQuests\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Item;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\MapNameValue;
use App\Game\GuideQuests\Services\GuideQuestRequirementsService;
use App\Game\Skills\Values\SkillTypeValue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGuideQuest;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateQuest;

class GuideQuestRequirementsServiceTest extends TestCase
{
    use CreateGuideQuest, CreateItem, CreateGameSkill, CreateQuest, CreateNpc, CreateGameMap, RefreshDatabase;

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
            'required_skill_type' => SkillTypeValue::EFFECTS_CLASS,
            'required_skill_type_level' => 1,
        ]);

        $character = $this->character->assignSkill(
            $this->createGameSkill([
                'type' => SkillTypeValue::EFFECTS_CLASS,
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
            'required_skill_type' => SkillTypeValue::CRAFTING,
            'required_skill_type_level' => 1,
        ]);

        $character = $this->character->assignSkill(
            $this->createGameSkill([
                'type' => SkillTypeValue::CRAFTING
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
            ->with('999 does not exist.');

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
}
