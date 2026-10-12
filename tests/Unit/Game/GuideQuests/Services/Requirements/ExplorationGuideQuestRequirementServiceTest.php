<?php

namespace Tests\Unit\Game\GuideQuests\Services\Requirements;

use App\Game\GuideQuests\Services\Requirements\ExplorationGuideQuestRequirementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateDelveAutomation;
use Tests\Traits\CreateGuideQuest;

class ExplorationGuideQuestRequirementServiceTest extends TestCase
{
    use CreateDelveAutomation, CreateGuideQuest, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?ExplorationGuideQuestRequirementService $guideQuestRequirementsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem();

        $this->guideQuestRequirementsService = new ExplorationGuideQuestRequirementService;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->guideQuestRequirementsService = null;
    }

    public function test_required_delve_pack_size_returns_false_when_no_delve_row_exists(): void
    {
        $guideQuest = $this->createGuideQuest([
            'required_delve_pack_size' => 5,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

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

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

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

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_delve_pack_size', $finishedRequirements);
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

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_delve_pack_size', $finishedRequirements);
    }
}
