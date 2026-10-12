<?php

namespace Tests\Unit\Game\GuideQuests\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GuideQuest;
use App\Game\GuideQuests\Services\GuideQuestRequirementsService;
use App\Game\GuideQuests\Services\Requirements\CharacterGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\CraftingGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\ExplorationGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\GlobalEventGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\KingdomGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\SkillGuideQuestRequirementService;
use App\Game\GuideQuests\Services\Requirements\WorldGuideQuestRequirementService;
use Mockery;
use Tests\TestCase;

class GuideQuestRequirementsServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_family_results_are_merged_and_duplicate_keys_are_emitted_once(): void
    {
        $character = Mockery::mock(Character::class);
        $quest = Mockery::mock(GuideQuest::class);
        $characterRequirements = Mockery::mock(CharacterGuideQuestRequirementService::class);
        $skillRequirements = Mockery::mock(SkillGuideQuestRequirementService::class);
        $explorationRequirements = Mockery::mock(ExplorationGuideQuestRequirementService::class);
        $worldRequirements = Mockery::mock(WorldGuideQuestRequirementService::class);
        $kingdomRequirements = Mockery::mock(KingdomGuideQuestRequirementService::class);
        $craftingRequirements = Mockery::mock(CraftingGuideQuestRequirementService::class);
        $globalEventRequirements = Mockery::mock(GlobalEventGuideQuestRequirementService::class);

        $characterRequirements->expects('completedRequirements')->with($character, $quest)->andReturn(['required_level']);
        $skillRequirements->expects('completedRequirements')->with($character, $quest)->andReturn(['required_skill_level']);
        $explorationRequirements->expects('completedRequirements')->with($character, $quest)->andReturn([]);
        $worldRequirements->expects('completedRequirements')->with($character, $quest)->andReturn(['required_level']);
        $kingdomRequirements->expects('completedRequirements')->with($character, $quest)->andReturn([]);
        $craftingRequirements->expects('completedRequirements')->with($character, $quest)->andReturn([]);
        $globalEventRequirements->expects('completedRequirements')->with($character, $quest)->andReturn([]);

        $service = new GuideQuestRequirementsService(
            $characterRequirements,
            $skillRequirements,
            $explorationRequirements,
            $worldRequirements,
            $kingdomRequirements,
            $craftingRequirements,
            $globalEventRequirements,
        );

        $requirements = $service->completedRequirements($character, $quest);

        $this->assertSame(['required_level', 'required_skill_level'], $requirements);
    }

    public function test_empty_family_results_produce_an_empty_result(): void
    {
        $character = Mockery::mock(Character::class);
        $quest = Mockery::mock(GuideQuest::class);
        $characterRequirements = Mockery::mock(CharacterGuideQuestRequirementService::class);
        $skillRequirements = Mockery::mock(SkillGuideQuestRequirementService::class);
        $explorationRequirements = Mockery::mock(ExplorationGuideQuestRequirementService::class);
        $worldRequirements = Mockery::mock(WorldGuideQuestRequirementService::class);
        $kingdomRequirements = Mockery::mock(KingdomGuideQuestRequirementService::class);
        $craftingRequirements = Mockery::mock(CraftingGuideQuestRequirementService::class);
        $globalEventRequirements = Mockery::mock(GlobalEventGuideQuestRequirementService::class);

        $characterRequirements->expects('completedRequirements')->with($character, $quest)->andReturn([]);
        $skillRequirements->expects('completedRequirements')->with($character, $quest)->andReturn([]);
        $explorationRequirements->expects('completedRequirements')->with($character, $quest)->andReturn([]);
        $worldRequirements->expects('completedRequirements')->with($character, $quest)->andReturn([]);
        $kingdomRequirements->expects('completedRequirements')->with($character, $quest)->andReturn([]);
        $craftingRequirements->expects('completedRequirements')->with($character, $quest)->andReturn([]);
        $globalEventRequirements->expects('completedRequirements')->with($character, $quest)->andReturn([]);

        $service = new GuideQuestRequirementsService(
            $characterRequirements,
            $skillRequirements,
            $explorationRequirements,
            $worldRequirements,
            $kingdomRequirements,
            $craftingRequirements,
            $globalEventRequirements,
        );

        $requirements = $service->completedRequirements($character, $quest);

        $this->assertSame([], $requirements);
    }
}
