<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Skills\Partials;

use DB;
use Mail;
use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Skills\Partials\SkillModifiers;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class SkillModifiersTest extends TestCase
{
    use RefreshDatabase, CreateGameSkill, CreateUser, CreateMonster, CreateRole;

    public function setUp() : void {
        parent::setUp();
    }

    public function testTheComponentLoads() {
        Livewire::test(SkillModifiers::class, [
            'skill' => $this->createGameSkill(),
        ])->assertSee('Base Damage Modifier Per level:');
    }

    public function testTheComponentCallsUpdate() {
        $skill = $this->createGameSkill();

        Livewire::test(SkillModifiers::class)->call('update', $skill->id)->assertSet('skill.name', $skill->name);
    }

    public function testCompnentLoadsWithCharacterContainingSkill() {

        $skill = $this->createGameSkill();

        (new CharacterFactory)->createBaseCharacter();

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->call('update', $skill->id)->assertSee('Base Damage Modifier Per level:');
    }

    public function testCompnentLoadsWithMonsterContainingSkill() {
        $monster = $this->createMonster();

        $skill = $this->createGameSkill();

        $monster->skills()->create([
            'monster_id' => $monster->id,
            'game_skill_id' => $skill->id,
            'level' => 1,
            'xp_max' => 999,
        ]);

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->call('update', $skill->id)->assertSee('Base Damage Modifier Per level:');
    }

    public function testCompnentLoadsWithMonsterAndCharacterContainingSkill() {
        $monster = $this->createMonster();

        $skill = $this->createGameSkill();

        $monster->skills()->create([
            'monster_id' => $monster->id,
            'game_skill_id' => $skill->id,
            'level' => 1,
            'xp_max' => 999,
        ]);

        (new CharacterFactory)->createBaseCharacter();

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->call('update', $skill->id)->assertSee('Base Damage Modifier Per level:');
    }

    public function testTheComponentCallsUpdateWithNull() {
        $skill = $this->createGameSkill();

        Livewire::test(SkillModifiers::class)->call('update', null)->assertNotSet('skill.name', $skill->name);
    }

    public function testFailToSaveModifierWhenMofiersAreEmpty() {
        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin($this->createAdminRole(), []));

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $skill = $this->createGameSkill([
            'base_damage_mod_bonus_per_level' => null,
            'base_healing_mod_bonus_per_level' => null,
            'base_ac_mod_bonus_per_level' => null,
            'fight_time_out_mod_bonus_per_level' => null,
            'move_time_out_mod_bonus_per_level' => null,
            'skill_bonus_per_level' => null,
        ]);

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->set('for', 'all')
          ->call('validateInput', 'nextStep', 2)
          ->assertSee('You must supply some kind of bonus per level.');


        // Assert skill was not applied:
        $this->assertNull($character->refresh()->skills()->where('game_skill_id', $skill->id)->first());
        $this->assertNull($monster->refresh()->skills()->where('game_skill_id', $skill->id)->first());
    }

    public function testFailToSaveModifierWhenMofiersAreBelowZero() {
        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin($this->createAdminRole(), []));

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $skill = $this->createGameSkill([
            'base_damage_mod_bonus_per_level' => -1.0,
            'base_healing_mod_bonus_per_level' => -1.0,
            'base_ac_mod_bonus_per_level' => -1.0,
            'fight_time_out_mod_bonus_per_level' => -1.0,
            'move_time_out_mod_bonus_per_level' => -1.0,
            'skill_bonus_per_level' => -1.0,
        ]);

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->set('for', 'all')
          ->call('validateInput', 'nextStep', 2)
          ->assertSee('No bonus may be below  or equal to: 0.');


        // Assert skill was not applied:
        $this->assertNull($character->refresh()->skills()->where('game_skill_id', $skill->id)->first());
        $this->assertNull($monster->refresh()->skills()->where('game_skill_id', $skill->id)->first());
    }

    public function testFailToSaveModifierWhenNoMonsterSelected() {
        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin($this->createAdminRole(), []));

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $skill = $this->createGameSkill();

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->set('for', 'select-monsters')
          ->call('validateInput', 'nextStep', 2)
          ->assertSee('At least one or more monsters must be selected.');


        // Assert skill was not applied:
        $this->assertNull($character->refresh()->skills()->where('game_skill_id', $skill->id)->first());
        $this->assertNull($monster->refresh()->skills()->where('game_skill_id', $skill->id)->first());
    }


    public function testAssignToAll() {
        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin($this->createAdminRole(), []));

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $skill = $this->createGameSkill();

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->set('for', 'all')
          ->call('validateInput', 'nextStep', 2);


        // Assert skill was applied:
        $this->assertNotNull($character->refresh()->skills()->where('game_skill_id', $skill->id)->first());
        $this->assertNotNull($monster->refresh()->skills()->where('game_skill_id', $skill->id)->first());
    }

    public function testDontAssignToAllWhenBothHaveTheSkill() {
        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin($this->createAdminRole(), []));

        $skill = $this->createGameSkill();

        $monster->skills()->create([
            'monster_id' => $monster->id,
            'game_skill_id' => $skill->id,
            'level' => 1,
            'xp_max' => 999,
        ]);

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->set('for', 'all')
          ->call('validateInput', 'nextStep', 2);


        // Assert skill was applied:
        $this->assertEquals(1, $character->refresh()->skills()->where('game_skill_id', $skill->id)->count());
        $this->assertEquals(1, $monster->refresh()->skills()->where('game_skill_id', $skill->id)->count());
    }

    public function testAssignToClasses() {

        $this->actingAs($this->createAdmin($this->createAdminRole(), []));

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $skill = $this->createGameSkill();

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->set('for', 'select-class')->set('skill.game_class_id', $character->class->id)
          ->call('validateInput', 'nextStep', 2);

        $this->assertNotNull($character->skills->where('game_skill_id', $skill->id)->first());
    }

    public function testAssignToAllWhenUserIsLoggedIn() {

        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin($this->createAdminRole(), []));

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();
        $skill = $this->createGameSkill();

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $character->user->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->set('for', 'all')
          ->call('validateInput', 'nextStep', 2);


        // Assert skill was applied:
        $this->assertNotNull($character->refresh()->skills()->where('game_skill_id', $skill->id)->first());
        $this->assertNotNull($monster->refresh()->skills()->where('game_skill_id', $skill->id)->first());
    }

    public function testAssignToMonster() {

        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin($this->createAdminRole(), []));

        $character = (new CharacterFactory)->createBaseCharacter()->getCharacter();

        $skill = $this->createGameSkill();

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->set('for', 'select-monsters')
          ->set('selectedMonsters', [$monster->id])
          ->call('validateInput', 'nextStep', 2);


        // Assert skill was applied:
        $this->assertNull($character->refresh()->skills()->where('game_skill_id', $skill->id)->first());
        $this->assertNotNull($monster->refresh()->skills()->where('game_skill_id', $skill->id)->first());
    }

    public function testDontAssignToMonsterWhenMonsterHasSkill() {
        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin($this->createAdminRole(), []));

        $skill = $this->createGameSkill();

        $monster->skills()->create([
            'monster_id' => $monster->id,
            'game_skill_id' => $skill->id,
            'level' => 1,
            'xp_max' => 999,
        ]);

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->set('for', 'select-monsters')
          ->set('selectedMonsters', [$monster->id])
          ->call('validateInput', 'nextStep', 2);


        $this->assertEquals(1, $monster->refresh()->skills()->where('game_skill_id', $skill->id)->count());
    }

    public function testFailToAssignToUnknownMonster() {
        $this->actingAs($this->createAdmin($this->createAdminRole(), []));

        $skill = $this->createGameSkill();

        Mail::fake();

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->set('for', 'select-monsters')
          ->set('selectedMonsters', [9999])
          ->call('validateInput', 'nextStep', 2);
    }

    public function testInitialSkillIsArray() {
        $skill = $this->createGameSkill();

        Livewire::test(SkillModifiers::class, ['skill' => $skill->toArray()])->assertSet('skill.name', $skill->name);
    }
}
