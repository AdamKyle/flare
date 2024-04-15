<?php

namespace Tests\Unit\Game\Character\CharacterCreation\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Character\CharacterCreation\Services\CharacterBuilderService;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreatePassiveSkill;

class CharacterBuilderServiceTest extends TestCase {

    use RefreshDatabase, CreateGameSkill, CreatePassiveSkill;

    private ?CharacterBuilderService $characterBuilderService;

    public function setUp(): void {

        parent::setUp();

        $this->characterBuilderService = resolve(CharacterBuilderService::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->characterBuilderService = null;
    }

    public function testSetAndGetCharacter() {
        $character = (new CharacterFactory())->createBaseCharacter()->getCharacter();

        $this->characterBuilderService->setCharacter($character);

        $this->assertEquals($character->name, $this->characterBuilderService->character()->name);
    }

    public function testAssignSkillsToCharacter() {

        $gameSkill = $this->createGameSkill([
            'name' => 'Game Skill'
        ]);

        $character = (new CharacterFactory())->createBaseCharacter([], [], false, false)->getCharacter();

        $classGameSkill = $this->createGameSkill([
            'game_class_id' => $character->game_class_id
        ]);

        $character = $this->characterBuilderService->setCharacter($character)->assignSkills()->character();

        $regularSkill = $character->skills()->where('game_skill_id', $gameSkill->id)->first();
        $classSkill = $character->skills()->where('game_skill_id', $classGameSkill->id)->first();

        $this->assertNotNull($regularSkill);
        $this->assertNotNull($classSkill);
    }

    public function testAssignPassiveSkillWithParentToCharacter() {
        $parentPassive = $this->createPassiveSkill([
            'is_parent' => true
        ]);

        $childPassive = $this->createPassiveSkill([
            'parent_skill_id' => $parentPassive->id,
            'unlocks_at_level' => 3,
        ]);

        $character = (new CharacterFactory())->createBaseCharacter([], [], false, false)->getCharacter();

        $character = $this->characterBuilderService->setCharacter($character)->assignPassiveSkills()->character();

        $characterParentPassive = $character->passiveSkills()->where('passive_skill_id', $parentPassive->id)->first();
        $characterChildPassive = $character->passiveSkills()->where('passive_skill_id', $childPassive->id)->where('parent_skill_id', $characterParentPassive->id)->first();

        $this->assertNotNull($characterParentPassive);
        $this->assertNotNull($characterChildPassive);
    }

    public function testDoesNotAssignPassiveSkillWhenCharacterHasPassive() {
        $parentPassive = $this->createPassiveSkill([
            'is_parent' => true
        ]);

        $childPassive = $this->createPassiveSkill([
            'parent_skill_id' => $parentPassive->id
        ]);

        $character = (new CharacterFactory())->createBaseCharacter([], [], false, false)->getCharacter();

        $character->passiveSkills()->create([
            'character_id'     => $character->id,
            'passive_skill_id' => $parentPassive->id,
            'current_level'    => 0,
            'hours_to_next'    => $parentPassive->hours_per_level,
            'is_locked'        => false,
            'parent_skill_id'  => null,
        ]);

        $character = $character->refresh();

        $character = $this->characterBuilderService->setCharacter($character)->assignPassiveSkills()->character();

        $characterParentPassive = $character->passiveSkills()->where('passive_skill_id', $parentPassive->id)->first();
        $characterChildPassive = $character->passiveSkills()->where('passive_skill_id', $childPassive->id)->where('parent_skill_id', $characterParentPassive->id)->first();

        $this->assertNotNull($characterParentPassive);
        $this->assertFalse($characterParentPassive->is_locked);
        $this->assertNotNull($characterChildPassive);
    }
}
