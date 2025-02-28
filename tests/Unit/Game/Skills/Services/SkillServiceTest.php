<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Events\SkillLeveledUpServerMessageEvent;
use App\Flare\Models\GameSkill;
use App\Game\Events\Values\EventType;
use App\Game\Skills\Services\SkillService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateScheduledEvent;

class SkillServiceTest extends TestCase
{
    use CreateClass, CreateGameSkill, CreateEvent, CreateScheduledEvent, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?SkillService $skillService;

    private ?GameSkill $skill;

    public function setUp(): void
    {
        parent::setUp();

        $this->skill = $this->createGameSkill([
            'name' => 'skill',
            'type' => SkillTypeValue::TRAINING->value,
            'can_train' => true,
        ]);

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->skill
        )->givePlayerLocation();

        $this->skillService = resolve(SkillService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->skill = null;
        $this->skillService = null;
    }

    public function testGetSkills()
    {
        $this->assertNotEmpty($this->skillService->getSkills($this->character->getCharacter(), [
            $this->skill->id,
        ]));
    }

    public function testGetSpecificSkill()
    {
        $this->assertNotEmpty($this->skillService->getSkill($this->character->getCharacter()->skills->first()));
    }

    public function testFailToTrainSkillThatDoesntExist()
    {
        $character = $this->character->getCharacter();

        $result = $this->skillService->trainSkill($character, 1, 10);

        $this->assertEquals(422, $result['status']);
        $this->assertEquals('Invalid Input.', $result['message']);
    }

    public function testTrainSkill()
    {
        $character = $this->character->getCharacter();
        $skillToTrain = $character->skills->first();

        $result = $this->skillService->trainSkill($character, $skillToTrain->id, .10);

        $skillToTrain = $skillToTrain->refresh();

        $this->assertEquals(0.10, $skillToTrain->xp_towards);
        $this->assertTrue($skillToTrain->currently_training);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('You are now training: ' . $skillToTrain->name, $result['message']);
    }

    public function testSwitchTrainingSkills()
    {
        $secondarySkill = $this->createGameSkill([
            'name' => 'skill',
            'type' => SkillTypeValue::TRAINING->value,
            'can_train' => true,
        ]);

        $character = $this->character->assignSkill($secondarySkill, 1, false, [
            'currently_training' => true,
            'skill_bonus' => 0.25,
        ])->getCharacter();

        $skillToTrain = $character->skills->first();
        $secondarySkillTraining = $character->skills->where('game_skill_id', $secondarySkill->id)->first();

        $result = $this->skillService->trainSkill($character, $skillToTrain->id, .10);

        $skillToTrain = $skillToTrain->refresh();
        $secondarySkillTraining = $secondarySkillTraining->refresh();

        $this->assertEquals(0.10, $skillToTrain->xp_towards);
        $this->assertTrue($skillToTrain->currently_training);

        $this->assertEquals(0.0, $secondarySkillTraining->xp_towards);
        $this->assertFalse($secondarySkillTraining->currently_training);

        $this->assertEquals(200, $result['status']);
        $this->assertEquals('You are now training: ' . $skillToTrain->name, $result['message']);
    }

    public function testDoNotAssignXpToASkillThatDoesntExist()
    {
        $character = $this->character->getCharacter();

        $this->skillService->setSkillInTraining($character)->assignXPToTrainingSkill($character, 10);

        $skill = $character->refresh()->skills->first();

        $this->assertNotEquals(10, $skill->xp);
    }

    public function testDoNotAssignXpToMaxLevelSkill()
    {
        $character = $this->character->getCharacter();

        $skill = $character->skills->first();

        $skill->update([
            'level' => $skill->baseSkill->max_level,
            'currently_training' => true,
        ]);

        $this->skillService->setSkillInTraining($character)->assignXPToTrainingSkill($character->refresh(), 10);

        $skill = $skill->refresh();

        $this->assertNotEquals(10, $skill->xp);
    }

    public function testAssignXpToSkill()
    {
        $character = $this->character->getCharacter();

        $skill = $character->skills->first();

        $skill->update([
            'currently_training' => true,
            'xp_towards' => 0.10,
        ]);

        $character = $character->refresh();

        $this->skillService->setSkillInTraining($character)->assignXPToTrainingSkill($character, 10);

        $skill = $character->refresh()->skills->where('currently_training', true)->first();

        $this->assertGreaterThan(10, $skill->xp);
    }

    public function testAssignXpToSkillWhenEventIsRunning()
    {
        $this->createScheduledEvent([
            'event_type' => EventType::FEEDBACK_EVENT,
            'start_date' => now()->addMinutes(5),
            'currently_running' => true,
        ]);

        $character = $this->character->getCharacter();

        $skill = $character->skills->first();

        $skill->update([
            'currently_training' => true,
            'xp_towards' => 0.10,
        ]);

        $character = $character->refresh();

        $this->skillService->setSkillInTraining($character)->assignXPToTrainingSkill($character, 10);

        $skill = $character->refresh()->skills->where('currently_training', true)->first();

        $this->assertGreaterThan(10, $skill->xp);
    }

    public function testLevelSkill()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $skill = $character->skills->first();

        $skill->update([
            'currently_training' => true,
            'xp_towards' => 0.10,
        ]);

        $character = $character->refresh();

        $this->skillService->setSkillInTraining($character)->assignXPToTrainingSkill($character, 1500);

        $skill = $character->refresh()->skills->where('currently_training', true)->first();

        $this->assertGreaterThan(2, $skill->level); // should be enough xp to go two or more levels.

        Event::assertDispatched(SkillLeveledUpServerMessageEvent::class);
    }

    public function testLevelSkillWhereSkillBonusWillNotGoAboveOne()
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $skill = $character->skills->first();

        $skill->update([
            'currently_training' => true,
            'xp_towards' => 0.10,
            'skill_bonus' => 1.0,
            'level' => $skill->baseSkill->max_level - 1,
        ]);

        $this->skillService->setSkillInTraining($character)->assignXPToTrainingSkill($character->refresh(), 1500);

        $skill = $character->refresh()->skills->first();

        $this->assertEquals($skill->baseSkill->max_level, $skill->level);
        $this->assertEquals(1, $skill->skill_bonus);

        Event::assertDispatched(SkillLeveledUpServerMessageEvent::class);
    }

    public function testCraftSkillDoesNotGetXpBecauseItsMaxLevel()
    {
        $craftingSkill = $this->createGameSkill([
            'type' => SkillTypeValue::CRAFTING->value,
            'name' => 'Weapon Crafting',
        ]);

        $character = $this->character->assignSkill($craftingSkill, 400)->getCharacter();
        $skill = $character->skills->where('game_skill_id', $craftingSkill->id)->first();

        $this->skillService->assignXpToCraftingSkill($character->map->gameMap, $skill);

        $this->assertEquals(0, $skill->refresh()->xp);
    }

    public function testAssignXpToCraftingSkillWhenFeedBackEventIsRunning()
    {
        $this->createScheduledEvent([
            'event_type' => EventType::FEEDBACK_EVENT,
            'start_date' => now()->addMinutes(5),
            'currently_running' => true,
        ]);

        $craftingSkill = $this->createGameSkill([
            'type' => SkillTypeValue::CRAFTING->value,
            'name' => 'Weapon Crafting',
        ]);

        $character = $this->character->assignSkill($craftingSkill, 1)->getCharacter();
        $skill = $character->skills->where('game_skill_id', $craftingSkill->id)->first();

        $this->skillService->assignXpToCraftingSkill($character->map->gameMap, $skill);

        $this->assertGreaterThan(0, $skill->refresh()->xp);
    }

    public function testAssignXpToRegularSkillWhenFeedBackEventIsRunning()
    {
        $this->createScheduledEvent([
            'event_type' => EventType::FEEDBACK_EVENT,
            'start_date' => now()->addMinutes(5),
            'currently_running' => true,
        ]);

        $regularSkill = $this->createGameSkill([
            'type' => SkillTypeValue::TRAINING->value,
            'name' => 'Accuracy',
        ]);

        $character = $this->character->assignSkill($regularSkill, 1)->getCharacter();
        $skill = $character->skills->where('game_skill_id', $regularSkill->id)->first();

        $this->skillService->assignXpToCraftingSkill($character->map->gameMap, $skill);

        $this->assertGreaterThan(0, $skill->refresh()->xp);
    }

    public function testAssignXpToRegularSkillAndLevelItUp()
    {
        $craftingSkill = $this->createGameSkill([
            'type' => SkillTypeValue::TRAINING->value,
            'name' => 'Accuracy',
        ]);

        $character = $this->character->assignSkill($craftingSkill, 1, false, [
            'xp' => 999,
        ])->getCharacter();
        $skill = $character->skills->where('game_skill_id', $craftingSkill->id)->first();

        $this->skillService->assignXpToCraftingSkill($character->map->gameMap, $skill);

        $this->assertGreaterThan(1, $skill->refresh()->level);
    }

    public function testAssignXpToRegularSkillAndDoNotLevelItUpWhenMaxed()
    {
        $craftingSkill = $this->createGameSkill([
            'type' => SkillTypeValue::TRAINING->value,
            'name' => 'Accuracy',
        ]);

        $character = $this->character->assignSkill($craftingSkill, 400, false, [
            'xp' => 999,
        ])->getCharacter();
        $skill = $character->skills->where('game_skill_id', $craftingSkill->id)->first();

        $this->skillService->assignXpToCraftingSkill($character->map->gameMap, $skill);

        $this->assertEquals(400, $skill->refresh()->level);
        $this->assertEquals(0, $skill->refresh()->xp);
    }

    public function testAssignXpToRegularSkillAndDoNotLevelItBeyondLevel400()
    {
        $craftingSkill = $this->createGameSkill([
            'type' => SkillTypeValue::TRAINING->value,
            'name' => 'Accuracy',
        ]);

        $character = $this->character->assignSkill($craftingSkill, 399, false, [
            'xp' => 999,
        ])->getCharacter();
        $skill = $character->skills->where('game_skill_id', $craftingSkill->id)->first();

        $this->skillService->assignXpToCraftingSkill($character->map->gameMap, $skill);

        $this->assertEquals(400, $skill->refresh()->level);
        $this->assertEquals(0, $skill->refresh()->xp);
    }
}
