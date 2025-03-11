<?php

namespace Tests\Unit\Game\ClassRanks\Services;

use App\Game\Character\CharacterInventory\Values\ItemType;
use App\Game\ClassRanks\Services\ClassRankService;
use App\Game\ClassRanks\Values\ClassRankValue;
use App\Game\ClassRanks\Values\ClassSpecialValue;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterClassRank;
use Tests\Traits\CreateCharacterClassSpecialitiesEquipped;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameClassSpecial;
use Tests\Traits\CreateGameSkill;

class ClassRanksServiceTest extends TestCase
{
    use CreateCharacterClassRank, CreateCharacterClassSpecialitiesEquipped, CreateClass, CreateGameClassSpecial, CreateGameSkill, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?ClassRankService $classRankService;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->assignSkill(
            $this->createGameSkill([
                'class_bonus' => 0.01,
            ]),
            5
        )->givePlayerLocation();

        $this->classRankService = resolve(ClassRankService::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->classRankService = null;
    }

    public function testGetClassRanks()
    {
        $response = $this->classRankService->getClassRanks($this->character->getCharacter());

        $this->assertEquals(200, $response['status']);
        $this->assertNotEmpty($response['class_ranks']);
    }

    public function testOneOfTheClassesIsLocked()
    {
        $heretic = $this->createClass([
            'name' => 'Heretic',
        ]);

        $thief = $this->createClass([
            'name' => 'Thief',
        ]);

        $prisonerClass = $this->createClass([
            'name' => 'Prisoner',
            'primary_required_class_id' => $heretic->id,
            'secondary_required_class_id' => $thief->id,
            'primary_required_class_level' => 10,
            'secondary_required_class_level' => 20,
        ]);

        $character = $this->character->addAdditionalClassRanks([$heretic->id, $thief->id, $prisonerClass->id])
            ->getCharacter();

        $response = $this->classRankService->getClassRanks($character);

        $this->assertEquals(200, $response['status']);

        $classRanks = $response['class_ranks'];

        $index = array_search(true, array_column($classRanks, 'is_locked'));

        $this->assertNotFalse($index);
    }

    public function testCannotEquipMoreThenThreeSpecialties()
    {
        $character = $this->character->getCharacter();

        $classSpecialOne = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $classSpecialTwo = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $classSpecialThree = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecialOne->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecialTwo->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecialThree->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $character = $character->refresh();

        $classSpecialFour = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $response = $this->classRankService->equipSpecialty($character, $classSpecialFour);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals('You have the maximum amount of specials (3) equipped. You cannot equip anymore.', $response['message']);
    }

    public function testCannotEquipAnotherDamageSpecial()
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $character = $character->refresh();

        $classSpecialTwo = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $response = $this->classRankService->equipSpecialty($character, $classSpecialTwo);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals('You already have a damage specialty equipped and cannot equip another one.', $response['message']);
    }

    public function testCannotEquipSpecialWhenLevelNotMet()
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
            'requires_class_rank_level' => 10,
        ]);

        $response = $this->classRankService->equipSpecialty($character, $classSpecial);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals('You do not have the required class rank level for this.', $response['message']);
    }

    public function testEquipClassSpecial()
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $response = $this->classRankService->equipSpecialty($character, $classSpecial);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals('Equipped class special: '.$classSpecial->name, $response['message']);
        $this->assertNotEmpty($response['specials_equipped']);
    }

    public function testEquipAnotherClassSpecialty()
    {
        $character = $this->character->getCharacter();

        $gameClass = $this->createClass(['name' => 'Heretic', 'damage_stat' => 'int']);

        $this->createCharacterClassRank([
            'character_id' => $character->id,
            'game_class_id' => $gameClass->id,
            'current_xp' => 0,
            'required_xp' => 0,
            'level' => 100,
        ]);

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $gameClass->id,
            'requires_class_rank_level' => 50,
        ]);

        $this->createCharacterClassRankSpecial([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 100,
            'current_xp' => 1,
            'required_xp' => 10,
            'equipped' => false,
        ]);

        $response = $this->classRankService->equipSpecialty($character, $classSpecial);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals('Equipped class special: '.$classSpecial->name, $response['message']);
        $this->assertNotEmpty($response['specials_equipped']);

        $character = $character->refresh();

        $this->assertEquals(1, $character->classSpecialsEquipped->count());
    }

    public function testRequipSpecialty()
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => false,
        ]);

        $character = $character->refresh();

        $response = $this->classRankService->equipSpecialty($character, $classSpecial);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals('Equipped class special: '.$classSpecial->name, $response['message']);
        $this->assertNotEmpty($response['specials_equipped']);

        $character = $character->refresh();

        $this->assertEquals(1, $character->classSpecialsEquipped->count());
    }

    public function testCannotUnequipSpecialtyYouDoNotOwn()
    {
        $character = $this->character->getCharacter();
        $characterTwo = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $characterTwo->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $classSpecialEquipped = $characterTwo->classSpecialsEquipped()->create([
            'character_id' => $characterTwo->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => false,
        ]);

        $characterTwo->refresh();

        $response = $this->classRankService->unequipSpecial($character, $classSpecialEquipped);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals('You do not own that.', $response['message']);
    }

    public function testCanUnequipSpecialty()
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $classSpecialEquipped = $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $character = $character->refresh();

        $response = $this->classRankService->unequipSpecial($character, $classSpecialEquipped);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals('Unequipped class special: '.$classSpecialEquipped->gameClassSpecial->name, $response['message']);
        $this->assertEmpty($response['specials_equipped']);
    }

    public function testNoXpForMaxLevel()
    {
        $character = $this->character->getCharacter();

        $character->classRanks()->update(['level' => ClassRankValue::MAX_LEVEL]);

        $character = $character->refresh();

        $this->classRankService->giveXpToClassRank($character);

        $character = $character->refresh();

        foreach ($character->classRanks as $rank) {
            $this->assertEquals(0, $rank->current_xp);
        }
    }

    public function testNoExpForNoInventory()
    {
        $character = $this->character->getCharacter();

        $character->inventory->slots()->update(['equipped' => false]);

        $character = $character->refresh();

        $this->classRankService->giveXpToClassRank($character);

        $character = $character->refresh();

        foreach ($character->classRanks as $rank) {
            $this->assertEquals(0, $rank->current_xp);
        }
    }

    public function testGainLevelInClassRank()
    {
        $character = $this->character->getCharacter();

        $currentlevel = $character->classRanks->first()->level;

        $this->classRankService->giveXpToClassRank($character);

        $character = $character->refresh();

        $newLevel = $character->classRanks->first()->level;

        $this->assertNotEquals($currentlevel, $newLevel);
    }

    public function testDoNotLevelUpSpecialtyWhenAtMax()
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $classSpecialEquipped = $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => ClassSpecialValue::MAX_LEVEL,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $currentLevel = $classSpecialEquipped->level;

        $character = $character->refresh();

        $this->classRankService->giveXpToEquippedClassSpecialties($character);

        $character = $character->refresh();

        $newlevel = $character->classSpecialsEquipped->first()->level;

        $this->assertEquals($currentLevel, $newlevel);
    }

    public function testLevelUpSpecialty()
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $classSpecialEquipped = $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $currentLevel = $classSpecialEquipped->level;

        $character = $character->refresh();

        $this->classRankService->giveXpToEquippedClassSpecialties($character);

        $character = $character->refresh();

        $newlevel = $character->classSpecialsEquipped->first()->level;

        $this->assertNotequals($currentLevel, $newlevel);
    }

    public function testDoNotLevelWeaponSpeacitlyWhenAtMaxLevel()
    {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        foreach ($character->classRanks as $rank) {
            foreach ($rank->weaponMasteries as $mastery) {
                $mastery->update([
                    'level' => WeaponMasteryValue::MAX_LEVEL,
                ]);
            }
        }

        $character = $character->refresh();

        $this->classRankService->giveXpToMasteries($character);

        $character = $character->refresh();

        foreach ($character->classRanks as $rank) {
            foreach ($rank->weaponMasteries as $mastery) {
                $this->assertEquals(WeaponMasteryValue::MAX_LEVEL, $mastery->level);
            }
        }
    }

    public function testDoNotGiveXPToMasteriesWhenNoInventory()
    {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        $character->inventory->slots()->update(['equipped' => false]);

        $character = $character->refresh();

        $this->classRankService->giveXpToMasteries($character);

        $character = $character->refresh();

        foreach ($character->classRanks as $rank) {
            foreach ($rank->weaponMasteries as $mastery) {
                $this->assertEquals(0, $mastery->current_xp);
            }
        }
    }

    public function testLevelEquippedItemSpecialty()
    {
        $character = $this->character->equipStartingEquipment()->getCharacter();

        foreach ($character->classRanks as $rank) {
            foreach ($rank->weaponMasteries as $mastery) {
                $mastery->update([
                    'level' => 0,
                ]);
            }
        }

        $character = $character->refresh();

        $equippedItemType = $character->inventory->slots()->where('equipped', true)->first()->item->type;

        $this->classRankService->giveXpToMasteries($character);

        $character = $character->refresh();

        foreach ($character->classRanks as $rank) {
            foreach ($rank->weaponMasteries as $mastery) {
                if (in_array($equippedItemType, ItemType::allWeaponTypes()) && $mastery->weapon_type === $equippedItemType) {
                    $this->assertEquals(1, $mastery->level);

                    continue;
                }

                $this->assertEquals(0, $mastery->level);
            }
        }
    }
}
