<?php

namespace Tests\Console\ClassRanks\Console\Commands;

use App\Game\Character\CharacterInventory\Values\ItemType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;

class AssignNewClassRanksTest extends TestCase
{
    use CreateClass, RefreshDatabase;

    public function test_command_creates_class_rank_for_missing_game_class()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $newGameClass = $this->createClass(['name' => 'NewTestClass']);

        $this->artisan('assign:new-class-ranks');

        $this->assertNotNull($character->refresh()->classRanks()->where('game_class_id', $newGameClass->id)->first());
    }

    public function test_command_creates_weapon_masteries_for_new_class_rank()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $newGameClass = $this->createClass(['name' => 'MasteryTestClass']);

        $this->artisan('assign:new-class-ranks');

        $classRank = $character->refresh()->classRanks()->where('game_class_id', $newGameClass->id)->first();

        $this->assertEquals(count(ItemType::allWeaponTypes()), $classRank->weaponMasteries()->count());
    }

    public function test_command_does_not_duplicate_existing_class_rank()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $existingClassId = $character->game_class_id;

        $this->artisan('assign:new-class-ranks');

        $this->assertEquals(1, $character->refresh()->classRanks()->where('game_class_id', $existingClassId)->count());
    }

    public function test_command_runs_successfully_with_no_characters()
    {
        $this->assertEquals(0, $this->artisan('assign:new-class-ranks'));
    }

    public function test_command_creates_ranks_for_all_characters()
    {
        $characterOne = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $characterTwo = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $newGameClass = $this->createClass(['name' => 'MultiCharClass']);

        $this->artisan('assign:new-class-ranks');

        $this->assertNotNull($characterOne->refresh()->classRanks()->where('game_class_id', $newGameClass->id)->first());
        $this->assertNotNull($characterTwo->refresh()->classRanks()->where('game_class_id', $newGameClass->id)->first());
    }

    public function test_command_does_not_modify_existing_class_rank_data()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $existingRank = $character->classRanks()->first();
        $originalXp = $existingRank->current_xp;
        $originalLevel = $existingRank->level;

        $this->artisan('assign:new-class-ranks');

        $existingRank = $existingRank->refresh();

        $this->assertEquals($originalXp, $existingRank->current_xp);
        $this->assertEquals($originalLevel, $existingRank->level);
    }

    public function test_command_uses_preferred_weapon_mastery_default_levels()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $rangerClass = $this->createClass(['name' => 'Ranger']);

        $this->artisan('assign:new-class-ranks');

        $classRank = $character->refresh()->classRanks()->where('game_class_id', $rangerClass->id)->first();
        $bowMastery = $classRank->weaponMasteries()->where('weapon_type', ItemType::BOW->value)->first();

        $this->assertEquals(5, $bowMastery->level);
    }

    public function test_command_does_not_duplicate_weapon_masteries_when_run_twice()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $newGameClass = $this->createClass(['name' => 'DuplicateMasteryClass']);

        $this->artisan('assign:new-class-ranks');
        $this->artisan('assign:new-class-ranks');

        $classRank = $character->refresh()->classRanks()->where('game_class_id', $newGameClass->id)->first();

        $this->assertEquals(count(ItemType::allWeaponTypes()), $classRank->weaponMasteries()->count());
    }

    public function test_command_is_idempotent_when_run_twice()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $newGameClass = $this->createClass(['name' => 'IdempotentClass']);

        $this->artisan('assign:new-class-ranks');
        $this->artisan('assign:new-class-ranks');

        $this->assertEquals(1, $character->refresh()->classRanks()->where('game_class_id', $newGameClass->id)->count());
    }
}
