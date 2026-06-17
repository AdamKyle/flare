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

    public function testCommandCreatesClassRankForMissingGameClass()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $newGameClass = $this->createClass(['name' => 'NewTestClass']);

        $this->artisan('assign:new-class-ranks');

        $this->assertNotNull($character->refresh()->classRanks()->where('game_class_id', $newGameClass->id)->first());
    }

    public function testCommandCreatesWeaponMasteriesForNewClassRank()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $newGameClass = $this->createClass(['name' => 'MasteryTestClass']);

        $this->artisan('assign:new-class-ranks');

        $classRank = $character->refresh()->classRanks()->where('game_class_id', $newGameClass->id)->first();

        $this->assertEquals(count(ItemType::allWeaponTypes()), $classRank->weaponMasteries()->count());
    }

    public function testCommandDoesNotDuplicateExistingClassRank()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $existingClassId = $character->game_class_id;

        $this->artisan('assign:new-class-ranks');

        $this->assertEquals(1, $character->refresh()->classRanks()->where('game_class_id', $existingClassId)->count());
    }

    public function testCommandRunsSuccessfullyWithNoCharacters()
    {
        $this->assertEquals(0, $this->artisan('assign:new-class-ranks'));
    }

    public function testCommandCreatesRanksForAllCharacters()
    {
        $characterOne = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $characterTwo = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $newGameClass = $this->createClass(['name' => 'MultiCharClass']);

        $this->artisan('assign:new-class-ranks');

        $this->assertNotNull($characterOne->refresh()->classRanks()->where('game_class_id', $newGameClass->id)->first());
        $this->assertNotNull($characterTwo->refresh()->classRanks()->where('game_class_id', $newGameClass->id)->first());
    }

    public function testCommandDoesNotModifyExistingClassRankData()
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

    public function testCommandUsesPreferredWeaponMasteryDefaultLevels()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $rangerClass = $this->createClass(['name' => 'Ranger']);

        $this->artisan('assign:new-class-ranks');

        $classRank = $character->refresh()->classRanks()->where('game_class_id', $rangerClass->id)->first();
        $bowMastery = $classRank->weaponMasteries()->where('weapon_type', ItemType::BOW->value)->first();

        $this->assertEquals(5, $bowMastery->level);
    }

    public function testCommandDoesNotDuplicateWeaponMasteriesWhenRunTwice()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $newGameClass = $this->createClass(['name' => 'DuplicateMasteryClass']);

        $this->artisan('assign:new-class-ranks');
        $this->artisan('assign:new-class-ranks');

        $classRank = $character->refresh()->classRanks()->where('game_class_id', $newGameClass->id)->first();

        $this->assertEquals(count(ItemType::allWeaponTypes()), $classRank->weaponMasteries()->count());
    }

    public function testCommandIsIdempotentWhenRunTwice()
    {
        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $newGameClass = $this->createClass(['name' => 'IdempotentClass']);

        $this->artisan('assign:new-class-ranks');
        $this->artisan('assign:new-class-ranks');

        $this->assertEquals(1, $character->refresh()->classRanks()->where('game_class_id', $newGameClass->id)->count());
    }
}
