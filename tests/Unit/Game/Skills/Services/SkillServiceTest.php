<?php

namespace Tests\Unit\Game\Skills\Services;

use App\Flare\Events\SkillLeveledUpServerMessageEvent;
use App\Flare\Models\GameSkill;
use App\Game\Skills\Services\SkillService;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateEvent;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;

class SkillServiceTest extends TestCase
{
    use CreateClass, CreateGameSkill, CreateEvent, CreateItem, RefreshDatabase;

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

        $skill = $character->refresh()->skills->first();

        $this->assertGreaterThan(2, $skill->level);

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
            'max_level' => 400,
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
            'max_level' => 400,
        ]);

        $character = $this->character->assignSkill($craftingSkill, 399, false, [
            'xp' => 999,
        ])->getCharacter();
        $skill = $character->skills->where('game_skill_id', $craftingSkill->id)->first();

        $this->skillService->assignXpToCraftingSkill($character->map->gameMap, $skill);

        $this->assertEquals(400, $skill->refresh()->level);
        $this->assertEquals(0, $skill->refresh()->xp);
    }

    public function testGetSkillUsesClampedLevelWhenSkillIsAboveMaxLevel()
    {
        $character = $this->character->getCharacter();

        $skill = $character->skills->first();

        $skill->baseSkill->update([
            'skill_bonus_per_level' => 0.1,
            'max_level' => 5,
        ]);

        $skill->update([
            'level' => 10,
            'skill_bonus' => 0.0,
        ]);

        $result = $this->skillService->getSkill($skill->refresh());

        $this->assertSame(1.0, $result['skill_bonus']);
    }

    public function testGetSkillStacksBoonSkillBonusByAmountUsed()
    {
        $character = $this->character->getCharacter();

        $skill = $character->skills->where('game_skill_id', $this->skill->id)->first();

        $skill->baseSkill->update([
            'skill_bonus_per_level' => 0.10,
            'max_level' => 10,
        ]);

        $skill->update([
            'level' => 1,
            'skill_bonus' => 0.0,
        ]);

        $boon = $this->createItem([
            'name' => 'Stacked Skill Bonus Boon',
            'increase_skill_bonus_by' => 0.15,
            'can_stack' => true,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $boon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 4,
        ]);

        $result = $this->skillService->getSkill($skill->refresh());

        $this->assertEqualsWithDelta(0.60, $result['skill_bonus'], 0.00001);
    }

    public function testGetSkillStacksBoonSkillTrainingBonusByAmountUsed()
    {
        $character = $this->character->getCharacter();

        $skill = $character->skills->where('game_skill_id', $this->skill->id)->first();

        $boon = $this->createItem([
            'name' => 'Stacked Skill Training Boon',
            'increase_skill_training_bonus_by' => 0.15,
            'can_stack' => true,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $boon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 4,
        ]);

        $result = $this->skillService->getSkill($skill->refresh());

        $this->assertEqualsWithDelta(0.60, $result['skill_xp_bonus'], 0.00001);
    }

    public function testGetSkillStacksBoonBaseDamageModByAmountUsed()
    {
        $character = $this->character->getCharacter();

        $skill = $character->skills->where('game_skill_id', $this->skill->id)->first();

        $skill->baseSkill->update([
            'base_damage_mod_bonus_per_level' => 0.10,
        ]);

        $skill->update([
            'level' => 1,
        ]);

        $boon = $this->createItem([
            'name' => 'Stacked Base Damage Boon',
            'base_damage_mod_bonus' => 0.15,
            'can_stack' => true,
        ]);

        $character->boons()->create([
            'character_id' => $character->id,
            'item_id' => $boon->id,
            'started' => now(),
            'complete' => now()->addMinutes(120),
            'last_for_minutes' => 120,
            'amount_used' => 4,
        ]);

        $result = $this->skillService->getSkill($skill->refresh());

        $this->assertEqualsWithDelta(0.70, $result['base_damage_mod'], 0.00001);
    }
}
