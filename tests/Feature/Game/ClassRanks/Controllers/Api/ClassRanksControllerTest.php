<?php

namespace Tests\Feature\Game\ClassRanks\Controllers\Api;

use App\Flare\Models\CharacterAutomation;
use App\Flare\Values\AutomationType;
use App\Flare\Values\BaseSkillValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameClassSpecial;
use Tests\Traits\CreateGameSkill;

class ClassRanksControllerTest extends TestCase
{
    use CreateClass, CreateGameClassSpecial, CreateGameSkill, RefreshDatabase;

    private ?CharacterFactory $character = null;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
    }

    public function testGetCharacterClassRanks()
    {

        $character = $this->character->getCharacter();

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/class-ranks/'.$character->id);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['class_ranks']);
    }

    public function testExplorationAllowsClassRankList(): void
    {
        $character = $this->character->getCharacter();
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)->call('GET', '/api/class-ranks/'.$character->id);

        $response->assertOk();

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['class_ranks']);
    }

    public function testDelveAllowsClassRankList(): void
    {
        $character = $this->character->getCharacter();
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)->call('GET', '/api/class-ranks/'.$character->id);

        $response->assertOk();

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['class_ranks']);
    }

    public function testFactionLoyaltyAllowsClassRankList(): void
    {
        $character = $this->character->getCharacter();
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)->call('GET', '/api/class-ranks/'.$character->id);

        $response->assertOk();

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['class_ranks']);
    }

    public function testGetCharacterClassSpecials()
    {

        $character = $this->character->getCharacter();

        $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/class-ranks/'.$character->id.'/specials');

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['class_specialties']);
        $this->assertCount(0, $jsonData['specials_equipped']);
        $this->assertCount(1, $jsonData['class_ranks']);
        $this->assertCount(0, $jsonData['other_class_specials']);
    }

    public function testExplorationAllowsCharacterClassSpecials(): void
    {
        $character = $this->character->getCharacter();
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->addHour(),
        ]);

        $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/class-ranks/'.$character->id.'/specials');

        $response->assertOk();

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['class_specialties']);
    }

    public function testDelveAllowsCharacterClassSpecials(): void
    {
        $character = $this->character->getCharacter();
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->addHour(),
        ]);

        $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/class-ranks/'.$character->id.'/specials');

        $response->assertOk();

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['class_specialties']);
    }

    public function testFactionLoyaltyAllowsCharacterClassSpecials(): void
    {
        $character = $this->character->getCharacter();
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->addHour(),
        ]);

        $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('GET', '/api/class-ranks/'.$character->id.'/specials');

        $response->assertOk();

        $jsonData = json_decode($response->getContent(), true);

        $this->assertCount(1, $jsonData['class_specialties']);
    }

    public function testEquipSpecial()
    {

        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/equip-specialty/'.$character->id.'/'.$classSpecial->id, [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Equipped class special: '.$classSpecial->name, $jsonData['message']);
    }

    public function testExplorationBlocksSwitchClassAndDoesNotChangeClass(): void
    {
        $character = $this->character->getCharacter();
        $initialClassId = $character->game_class_id;
        $gameClass = $this->createClass(['name' => 'Heretic']);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/switch-classes/'.$character->id.'/'.$gameClass->id, [
                '_token' => csrf_token(),
            ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $this->assertEquals($initialClassId, $character->refresh()->game_class_id);
    }

    public function testDelveBlocksSwitchClassAndDoesNotChangeClass(): void
    {
        $character = $this->character->getCharacter();
        $initialClassId = $character->game_class_id;
        $gameClass = $this->createClass(['name' => 'Heretic']);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/switch-classes/'.$character->id.'/'.$gameClass->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertStatus(422);
        $this->assertEquals($initialClassId, $character->refresh()->game_class_id);
    }

    public function testFactionLoyaltyBlocksSwitchClassAndDoesNotChangeClass(): void
    {
        $character = $this->character->getCharacter();
        $initialClassId = $character->game_class_id;
        $gameClass = $this->createClass(['name' => 'Heretic']);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/switch-classes/'.$character->id.'/'.$gameClass->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertStatus(422);
        $this->assertEquals($initialClassId, $character->refresh()->game_class_id);
    }

    public function testExplorationBlocksEquipSpecialAndDoesNotEquip(): void
    {
        $character = $this->character->getCharacter();
        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/equip-specialty/'.$character->id.'/'.$classSpecial->id, [
                '_token' => csrf_token(),
            ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $this->assertCount(0, $character->refresh()->classSpecialsEquipped);
    }

    public function testDelveBlocksEquipSpecialAndDoesNotEquip(): void
    {
        $character = $this->character->getCharacter();
        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/equip-specialty/'.$character->id.'/'.$classSpecial->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertStatus(422);
        $this->assertCount(0, $character->refresh()->classSpecialsEquipped);
    }

    public function testFactionLoyaltyBlocksEquipSpecialAndDoesNotEquip(): void
    {
        $character = $this->character->getCharacter();
        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/equip-specialty/'.$character->id.'/'.$classSpecial->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertStatus(422);
        $this->assertCount(0, $character->refresh()->classSpecialsEquipped);
    }

    public function testExpiredExplorationDoesNotBlockEquipSpecial(): void
    {
        $character = $this->character->getCharacter();
        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/equip-specialty/'.$character->id.'/'.$classSpecial->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertOk();
        $this->assertCount(1, $character->refresh()->classSpecialsEquipped);
    }

    public function testExpiredDelveDoesNotBlockEquipSpecial(): void
    {
        $character = $this->character->getCharacter();
        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/equip-specialty/'.$character->id.'/'.$classSpecial->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertOk();
        $this->assertCount(1, $character->refresh()->classSpecialsEquipped);
    }

    public function testExpiredFactionLoyaltyDoesNotBlockEquipSpecial(): void
    {
        $character = $this->character->getCharacter();
        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
        ]);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/equip-specialty/'.$character->id.'/'.$classSpecial->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertOk();
        $this->assertCount(1, $character->refresh()->classSpecialsEquipped);
    }

    public function testExpiredExplorationDoesNotBlockSwitchClass(): void
    {
        $character = $this->character->getCharacter();
        $skill = $this->createGameSkill(['name' => 'Class Skill', 'game_class_id' => $character->game_class_id]);
        $skillData = (new BaseSkillValue)->getBaseCharacterSkillValue($character, $skill);
        $skillData['is_locked'] = false;
        $character->skills()->create($skillData);
        $gameClass = $this->createClass(['name' => 'Heretic']);
        $this->createGameSkill(['name' => 'Heretic Skill', 'game_class_id' => $gameClass->id]);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/switch-classes/'.$character->id.'/'.$gameClass->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertOk();
        $this->assertEquals($gameClass->id, $character->refresh()->game_class_id);
    }

    public function testExpiredDelveDoesNotBlockSwitchClass(): void
    {
        $character = $this->character->getCharacter();
        $skill = $this->createGameSkill(['name' => 'Class Skill', 'game_class_id' => $character->game_class_id]);
        $skillData = (new BaseSkillValue)->getBaseCharacterSkillValue($character, $skill);
        $skillData['is_locked'] = false;
        $character->skills()->create($skillData);
        $gameClass = $this->createClass(['name' => 'Heretic']);
        $this->createGameSkill(['name' => 'Heretic Skill', 'game_class_id' => $gameClass->id]);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/switch-classes/'.$character->id.'/'.$gameClass->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertOk();
        $this->assertEquals($gameClass->id, $character->refresh()->game_class_id);
    }

    public function testExpiredFactionLoyaltyDoesNotBlockSwitchClass(): void
    {
        $character = $this->character->getCharacter();
        $skill = $this->createGameSkill(['name' => 'Class Skill', 'game_class_id' => $character->game_class_id]);
        $skillData = (new BaseSkillValue)->getBaseCharacterSkillValue($character, $skill);
        $skillData['is_locked'] = false;
        $character->skills()->create($skillData);
        $gameClass = $this->createClass(['name' => 'Heretic']);
        $this->createGameSkill(['name' => 'Heretic Skill', 'game_class_id' => $gameClass->id]);
        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/switch-classes/'.$character->id.'/'.$gameClass->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertOk();
        $this->assertEquals($gameClass->id, $character->refresh()->game_class_id);
    }

    public function testUnequipSpecial()
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $specialtyEquipped = $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        $character = $character->refresh();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/unequip-specialty/'.$character->id.'/'.$specialtyEquipped->id, [
                '_token' => csrf_token(),
            ]);

        $jsonData = json_decode($response->getContent(), true);

        $this->assertEquals('Unequipped class special: '.$classSpecial->name, $jsonData['message']);
    }

    public function testExplorationBlocksUnequipSpecialAndDoesNotUnequip(): void
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $specialtyEquipped = $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/unequip-specialty/'.$character->id.'/'.$specialtyEquipped->id, [
                '_token' => csrf_token(),
            ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $this->assertTrue($specialtyEquipped->refresh()->equipped);
    }

    public function testDelveBlocksUnequipSpecialAndDoesNotUnequip(): void
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $specialtyEquipped = $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/unequip-specialty/'.$character->id.'/'.$specialtyEquipped->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertStatus(422);
        $this->assertTrue($specialtyEquipped->refresh()->equipped);
    }

    public function testFactionLoyaltyBlocksUnequipSpecialAndDoesNotUnequip(): void
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $specialtyEquipped = $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/unequip-specialty/'.$character->id.'/'.$specialtyEquipped->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertStatus(422);
        $this->assertTrue($specialtyEquipped->refresh()->equipped);
    }

    public function testExpiredExplorationDoesNotBlockUnequipSpecial(): void
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $specialtyEquipped = $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::EXPLORING,
            'completed_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/unequip-specialty/'.$character->id.'/'.$specialtyEquipped->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertOk();
        $this->assertFalse($specialtyEquipped->refresh()->equipped);
    }

    public function testExpiredDelveDoesNotBlockUnequipSpecial(): void
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $specialtyEquipped = $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::DELVE,
            'completed_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/unequip-specialty/'.$character->id.'/'.$specialtyEquipped->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertOk();
        $this->assertFalse($specialtyEquipped->refresh()->equipped);
    }

    public function testExpiredFactionLoyaltyDoesNotBlockUnequipSpecial(): void
    {
        $character = $this->character->getCharacter();

        $classSpecial = $this->createGameClassSpecial([
            'game_class_id' => $character->game_class_id,
            'specialty_damage' => 50000,
            'increase_specialty_damage_per_level' => 50,
            'specialty_damage_uses_damage_stat_amount' => 0.10,
        ]);

        $specialtyEquipped = $character->classSpecialsEquipped()->create([
            'character_id' => $character->id,
            'game_class_special_id' => $classSpecial->id,
            'level' => 0,
            'current_xp' => 0,
            'required_xp' => 100,
            'equipped' => true,
        ]);

        CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'completed_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/unequip-specialty/'.$character->id.'/'.$specialtyEquipped->id, [
                '_token' => csrf_token(),
            ]);

        $response->assertOk();
        $this->assertFalse($specialtyEquipped->refresh()->equipped);
    }
}
