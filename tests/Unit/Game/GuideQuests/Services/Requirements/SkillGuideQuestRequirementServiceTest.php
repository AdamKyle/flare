<?php

namespace Tests\Unit\Game\GuideQuests\Services\Requirements;

use App\Flare\Models\GameSkill;
use App\Game\GuideQuests\Services\Requirements\SkillGuideQuestRequirementService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGuideQuest;

class SkillGuideQuestRequirementServiceTest extends TestCase
{
    use CreateGameSkill, CreateGuideQuest, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?SkillGuideQuestRequirementService $guideQuestRequirementsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation()->assignFactionSystem();

        $this->guideQuestRequirementsService = new SkillGuideQuestRequirementService;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->guideQuestRequirementsService = null;
    }

    public function test_get_required_skill_check()
    {
        $gameSkill = GameSkill::where('name', 'Accuracy')->first();

        $guideQuest = $this->createGuideQuest([
            'required_skill' => $gameSkill->id,
            'required_skill_level' => 1,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_skill_level', $finishedRequirements);
    }

    public function test_required_training_skill_requires_active_skill_at_configured_xp_sacrifice(): void
    {
        $gameSkill = GameSkill::where('name', 'Accuracy')->firstOrFail();

        $guideQuest = $this->createGuideQuest([
            'required_training_skill' => $gameSkill->id,
            'required_training_xp_sacrifice' => 0.10,
        ]);

        $character = $this->character->getCharacter();
        $accuracy = $character->skills()
            ->where('game_skill_id', $gameSkill->id)
            ->firstOrFail();

        $accuracy->update([
            'currently_training' => true,
            'xp_towards' => 0.10,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertContains('required_training_xp_sacrifice', $finishedRequirements);

        $accuracy->update([
            'xp_towards' => 0.25,
        ]);

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_training_xp_sacrifice', $finishedRequirements);
    }

    public function test_get_secondary_required_skill_check()
    {
        $gameSkill = GameSkill::where('name', 'Accuracy')->first();

        $guideQuest = $this->createGuideQuest([
            'required_secondary_skill' => $gameSkill->id,
            'required_secondary_skill_level' => 1,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

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

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

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

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

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

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertEmpty($finishedRequirements);
    }

    public function test_required_skill_check_returns_false_when_character_does_not_have_skill(): void
    {
        $gameSkill = $this->createGameSkill(['name' => 'NonexistentSkill'.uniqid()]);

        $guideQuest = $this->createGuideQuest([
            'required_skill' => $gameSkill->id,
            'required_skill_level' => 1,
        ]);

        $character = $this->character->getCharacter();

        $finishedRequirements = $this->guideQuestRequirementsService->completedRequirements($character->refresh(), $guideQuest);

        $this->assertNotContains('required_skill_level', $finishedRequirements);
    }
}
