<?php

namespace Tests\Unit\Game\ClassRanks\Services;

use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\ClassRanks\Services\ClassRankService;
use App\Game\ClassRanks\Values\ClassRankValue;
use App\Game\ClassRanks\Values\ClassSpecialValue;
use App\Game\ClassRanks\Values\WeaponMasteryValue;
use App\Game\Core\Items\Values\ItemType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterClassRank;
use Tests\Traits\CreateCharacterClassSpecialitiesEquipped;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameClassSpecial;
use Tests\Traits\CreateGameSkill;

class ClassRankServiceTest extends TestCase
{
    use CreateCharacterClassRank, CreateCharacterClassSpecialitiesEquipped, CreateClass, CreateGameClassSpecial, CreateGameSkill, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?ClassRankService $classRankService;

    protected function setUp(): void
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

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->classRankService = null;
    }

    public function test_get_class_ranks()
    {
        $response = $this->classRankService->getClassRanks($this->character->getCharacter());

        $this->assertEquals(200, $response['status']);
        $this->assertNotEmpty($response['class_ranks']);

        $classRank = $response['class_ranks'][0];

        $this->assertArrayHasKey('class_detail', $classRank);
        $this->assertArrayHasKey('weapon_masteries', $classRank);
        $this->assertArrayHasKey('is_active', $classRank);
        $this->assertArrayHasKey('is_locked', $classRank);
        $this->assertFalse($classRank['is_mastered']);
    }

    public function test_get_class_ranks_identifies_mastered_class_and_weapon_mastery(): void
    {
        $character = $this->character->getCharacter();

        $classRank = $character->classRanks()->where('game_class_id', $character->game_class_id)->first();
        $classRank->update(['level' => ClassRankValue::MAX_LEVEL]);

        $weaponMastery = $classRank->weaponMasteries()->first();
        $weaponMastery->update(['level' => WeaponMasteryValue::MAX_LEVEL]);

        $character = $character->refresh();

        $response = $this->classRankService->getClassRanks($character);
        $rankData = collect($response['class_ranks'])->firstWhere('game_class_id', $character->game_class_id);

        $this->assertTrue($rankData['is_mastered']);

        $masteryData = collect($rankData['weapon_masteries'])->firstWhere('id', $weaponMastery->id);

        $this->assertTrue($masteryData['is_mastered']);
    }

    public function test_class_rank_unlock_progress_returns_current_and_required_levels(): void
    {
        $primary = $this->createClass(['name' => 'Primary']);
        $secondary = $this->createClass(['name' => 'Secondary']);
        $lockedClass = $this->createClass([
            'name' => 'Locked Class',
            'primary_required_class_id' => $primary->id,
            'secondary_required_class_id' => $secondary->id,
            'primary_required_class_level' => 10,
            'secondary_required_class_level' => 20,
        ]);
        $character = $this->character->addAdditionalClassRanks([$primary->id, $secondary->id, $lockedClass->id])->getCharacter();
        $character->classRanks()->where('game_class_id', $primary->id)->update(['level' => 5]);
        $character->classRanks()->where('game_class_id', $secondary->id)->update(['level' => 20]);

        $response = $this->classRankService->getClassRanks($character->refresh());
        $classRank = collect($response['class_ranks'])->firstWhere('game_class_id', $lockedClass->id);

        $this->assertSame($primary->name, $classRank['unlock_progress']['primary']['name']);
        $this->assertSame(5, $classRank['unlock_progress']['primary']['current_level']);
        $this->assertSame(10, $classRank['unlock_progress']['primary']['required_level']);
        $this->assertFalse($classRank['unlock_progress']['primary']['is_met']);

        $this->assertSame($secondary->name, $classRank['unlock_progress']['secondary']['name']);
        $this->assertSame(20, $classRank['unlock_progress']['secondary']['current_level']);
        $this->assertSame(20, $classRank['unlock_progress']['secondary']['required_level']);
        $this->assertTrue($classRank['unlock_progress']['secondary']['is_met']);
    }

    public function test_get_specials_includes_reusable_class_mastery_detail(): void
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'requires_class_rank_level' => 5,
        ]);

        $response = $this->classRankService->getSpecials($character);

        $specialty = collect($response['class_specialties'])->firstWhere('id', $classSpecial->id);

        $this->assertSame($classSpecial->id, $specialty['class_mastery']['id']);
        $this->assertSame($classSpecial->name, $specialty['class_mastery']['name']);
        $this->assertSame($character->game_class_id, $specialty['class_mastery']['game_class']['id']);
        $this->assertSame(5, $specialty['class_mastery']['requires_class_rank_level']);
    }

    public function test_get_specials_includes_is_mastered_flag_for_equipped_and_other_specials(): void
    {
        $character = $this->character->getCharacter();

        $masteredSpecial = $this->createGameClassSpecial(['game_class_id' => $character->game_class_id]);
        $inProgressSpecial = $this->createGameClassSpecial(['game_class_id' => $character->game_class_id]);

        $this->createCharacterClassRankSpecial([
            'character_id' => $character->id,
            'game_class_special_id' => $masteredSpecial->id,
            'level' => ClassSpecialValue::MAX_LEVEL,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $this->createCharacterClassRankSpecial([
            'character_id' => $character->id,
            'game_class_special_id' => $inProgressSpecial->id,
            'level' => 1,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => false,
        ]);

        $response = $this->classRankService->getSpecials($character->refresh());

        $equippedRow = collect($response['specials_equipped'])->firstWhere('game_class_special_id', $masteredSpecial->id);
        $otherRow = collect($response['other_class_specials'])->firstWhere('game_class_special_id', $inProgressSpecial->id);

        $this->assertTrue($equippedRow['is_mastered']);
        $this->assertFalse($otherRow['is_mastered']);
    }

    public function test_one_of_the_classes_is_locked()
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

    public function test_class_is_locked_when_primary_prerequisite_is_below_required_level(): void
    {
        $primary = $this->createClass(['name' => 'Primary']);
        $secondary = $this->createClass(['name' => 'Secondary']);
        $lockedClass = $this->createClass([
            'name' => 'Locked Class',
            'primary_required_class_id' => $primary->id,
            'secondary_required_class_id' => $secondary->id,
            'primary_required_class_level' => 10,
            'secondary_required_class_level' => 20,
        ]);
        $character = $this->character->addAdditionalClassRanks([$primary->id, $secondary->id, $lockedClass->id])->getCharacter();
        $character->classRanks()->where('game_class_id', $primary->id)->update(['level' => 9]);
        $character->classRanks()->where('game_class_id', $secondary->id)->update(['level' => 20]);

        $response = $this->classRankService->getClassRanks($character->refresh());
        $classRank = collect($response['class_ranks'])->firstWhere('game_class_id', $lockedClass->id);

        $this->assertTrue($classRank['is_locked']);
    }

    public function test_class_is_locked_when_secondary_prerequisite_is_below_required_level(): void
    {
        $primary = $this->createClass(['name' => 'Primary']);
        $secondary = $this->createClass(['name' => 'Secondary']);
        $lockedClass = $this->createClass([
            'name' => 'Locked Class',
            'primary_required_class_id' => $primary->id,
            'secondary_required_class_id' => $secondary->id,
            'primary_required_class_level' => 10,
            'secondary_required_class_level' => 20,
        ]);
        $character = $this->character->addAdditionalClassRanks([$primary->id, $secondary->id, $lockedClass->id])->getCharacter();
        $character->classRanks()->where('game_class_id', $primary->id)->update(['level' => 10]);
        $character->classRanks()->where('game_class_id', $secondary->id)->update(['level' => 19]);

        $response = $this->classRankService->getClassRanks($character->refresh());
        $classRank = collect($response['class_ranks'])->firstWhere('game_class_id', $lockedClass->id);

        $this->assertTrue($classRank['is_locked']);
    }

    public function test_class_is_unlocked_when_both_prerequisites_are_satisfied(): void
    {
        $primary = $this->createClass(['name' => 'Primary']);
        $secondary = $this->createClass(['name' => 'Secondary']);
        $unlockedClass = $this->createClass([
            'name' => 'Unlocked Class',
            'primary_required_class_id' => $primary->id,
            'secondary_required_class_id' => $secondary->id,
            'primary_required_class_level' => 10,
            'secondary_required_class_level' => 20,
        ]);
        $character = $this->character->addAdditionalClassRanks([$primary->id, $secondary->id, $unlockedClass->id])->getCharacter();
        $character->classRanks()->where('game_class_id', $primary->id)->update(['level' => 10]);
        $character->classRanks()->where('game_class_id', $secondary->id)->update(['level' => 20]);

        $response = $this->classRankService->getClassRanks($character->refresh());
        $classRank = collect($response['class_ranks'])->firstWhere('game_class_id', $unlockedClass->id);

        $this->assertFalse($classRank['is_locked']);
    }

    public function test_class_without_prerequisite_pair_is_unlocked(): void
    {
        $unlockedClass = $this->createClass(['name' => 'Unlocked Class']);
        $character = $this->character->addAdditionalClassRanks([$unlockedClass->id])->getCharacter();

        $response = $this->classRankService->getClassRanks($character);
        $classRank = collect($response['class_ranks'])->firstWhere('game_class_id', $unlockedClass->id);

        $this->assertFalse($classRank['is_locked']);
    }

    public function test_cannot_equip_more_then_three_specialties()
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

    public function test_cannot_equip_another_damage_special()
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

    public function test_cannot_equip_special_when_level_not_met()
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

    public function test_equip_class_special()
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

    public function test_equip_another_class_specialty()
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

    public function test_requip_specialty()
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

    public function test_cannot_unequip_specialty_you_do_not_own()
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

    public function test_can_unequip_specialty()
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

    public function test_unequip_specialty_emits_update_character_base_details_event()
    {
        Event::fake([UpdateCharacterBaseDetailsEvent::class]);

        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
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

        $this->classRankService->unequipSpecial($character, $classSpecialEquipped);

        Event::assertDispatched(UpdateCharacterBaseDetailsEvent::class);
    }

    public function test_swap_specialty_successfully_replaces_equipped_specialty()
    {
        $character = $this->character->getCharacter();

        $currentlyEquipped = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $target = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $equipped = $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $currentlyEquipped->id,
            'level' => 3,
            'current_xp' => 10,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $character = $character->refresh();

        $response = $this->classRankService->swapSpecialty($character, $target, $equipped);

        $this->assertEquals(200, $response['status']);

        $character = $character->refresh();

        $this->assertFalse($character->classSpecialsEquipped->where('game_class_special_id', $currentlyEquipped->id)->first()->equipped);
        $this->assertTrue($character->classSpecialsEquipped->where('game_class_special_id', $target->id)->first()->equipped);
    }

    public function test_swap_specialty_preserves_existing_progress_for_previously_learned_target()
    {
        $character = $this->character->getCharacter();

        $currentlyEquipped = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $target = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $equipped = $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $currentlyEquipped->id,
            'level' => 1,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $existingProgress = $this->createCharacterClassRankSpecial([
            'character_id' => $character->id,
            'game_class_special_id' => $target->id,
            'level' => 7,
            'current_xp' => 42,
            'required_xp' => 500,
            'equipped' => false,
        ]);

        $character = $character->refresh();

        $this->classRankService->swapSpecialty($character, $target, $equipped);

        $existingProgress = $existingProgress->refresh();

        $this->assertTrue($existingProgress->equipped);
        $this->assertEquals(7, $existingProgress->level);
        $this->assertEquals(42, $existingProgress->current_xp);
    }

    public function test_swap_specialty_automatically_replaces_equipped_damage_specialty()
    {
        $character = $this->character->getCharacter();

        $equippedDamageSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $targetDamageSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 60000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $equipped = $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $equippedDamageSpecial->id,
            'level' => 1,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $character = $character->refresh();

        $response = $this->classRankService->swapSpecialty($character, $targetDamageSpecial, $equipped);

        $this->assertEquals(200, $response['status']);

        $character = $character->refresh();

        $this->assertFalse($character->classSpecialsEquipped->where('game_class_special_id', $equippedDamageSpecial->id)->first()->equipped);
        $this->assertTrue($character->classSpecialsEquipped->where('game_class_special_id', $targetDamageSpecial->id)->first()->equipped);
    }

    public function test_swap_specialty_fails_when_replacement_belongs_to_another_character()
    {
        $character = $this->character->getCharacter();
        $characterTwo = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $target = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $otherCharacterSpecial = $this->createGameClassSpecial([
            'game_class_id' => $characterTwo->game_class_id,
        ]);

        $otherCharacterEquipped = $characterTwo->classSpecialsEquipped()->create([
            'character_id' => $characterTwo->id,
            'game_class_special_id' => $otherCharacterSpecial->id,
            'level' => 1,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $response = $this->classRankService->swapSpecialty($character, $target, $otherCharacterEquipped);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals('You do not own that.', $response['message']);
    }

    public function test_equip_specialty_returns_requirement_error_when_class_rank_is_missing()
    {
        $character = $this->character->getCharacter();

        $otherClass = $this->createClass(['name' => 'Unranked Class']);

        $target = $this->createGameClassSpecial([
            'game_class_id' => $otherClass->id,
        ]);

        $response = $this->classRankService->equipSpecialty($character, $target);

        $this->assertEquals(422, $response['status']);
        $this->assertEquals('You do not have the required class rank level for this.', $response['message']);
    }

    public function test_no_xp_for_max_level()
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

    public function test_no_exp_for_no_inventory()
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

    public function test_gain_level_in_class_rank()
    {
        $character = $this->character->getCharacter();

        $currentlevel = $character->classRanks->first()->level;

        $this->classRankService->giveXpToClassRank($character);

        $character = $character->refresh();

        $newLevel = $character->classRanks->first()->level;

        $this->assertNotEquals($currentlevel, $newLevel);
    }

    public function test_do_not_level_up_specialty_when_at_max()
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

    public function test_level_up_specialty()
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

    public function test_do_not_level_weapon_speacitly_when_at_max_level()
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

    public function test_do_not_give_xp_to_masteries_when_no_inventory()
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

    public function test_level_equipped_item_specialty()
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
