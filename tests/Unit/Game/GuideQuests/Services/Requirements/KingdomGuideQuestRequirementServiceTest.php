<?php

namespace Tests\Unit\Game\GuideQuests\Services\Requirements;

use App\Flare\Models\GameBuilding;
use App\Game\GuideQuests\Services\Requirements\KingdomGuideQuestRequirementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGuideQuest;

class KingdomGuideQuestRequirementServiceTest extends TestCase
{
    use CreateGuideQuest, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?KingdomGuideQuestRequirementService $guideQuestRequirementsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem();

        $this->guideQuestRequirementsService = new KingdomGuideQuestRequirementService;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->guideQuestRequirementsService = null;
    }

    public function test_fetch_required_kingdoms_count()
    {
        $character = $this->character->kingdomManagement()->assignKingdom()->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_kingdoms' => 1,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

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

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

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

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

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

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

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

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_kingdom_building_level', $finishedRequirements);
    }

    public function test_fetch_required_kingdom_unit_amount()
    {
        $character = $this->character->kingdomManagement()->assignKingdom()->assignUnits([], 1000)->getCharacter();

        $guideQuest = $this->createGuideQuest([
            'required_kingdom_units' => 100,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

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

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_passive_level', $finishedRequirements);
    }

    public function test_required_kingdom_passive_level_returns_false_when_character_does_not_have_passive_skill(): void
    {
        $guideQuest = $this->createGuideQuest([
            'required_passive_level' => 1,
            'required_passive_skill' => 999999,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_passive_level', $finishedRequirements);
    }
}
