<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Skills\Partials;

use App\Admin\Mail\GenericMail;
use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Skills\Partials\SkillModifiers;
use App\Flare\Models\Item;
use Auth;
use Database\Seeders\GameSkillsSeeder;
use DB;
use Event;
use Mail;
use Queue;
use Tests\Setup\CharacterSetup;
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

    public function testTheComponentCallsUpdateWithNull() {
        $skill = $this->createGameSkill();

        Livewire::test(SkillModifiers::class)->call('update', null)->assertNotSet('skill.name', $skill->name);
    }

    public function testFailToSaveModifierWhenMofiersAreEmpty() {
        $this->seed(GameSkillsSeeder::class);

        $user = $this->createUser();

        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin([], $this->createAdminRole()));

        $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->setSkill('Accuracy', ['skill_bonus_per_level' => 10], [
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->setSkill('Dodge', [
                                            'skill_bonus_per_level' => 10,
                                        ])
                                        ->setSkill('Looting', [
                                            'skill_bonus_per_level' => 0,
                                        ])
                                        ->getCharacter();
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
        $this->seed(GameSkillsSeeder::class);

        $user = $this->createUser();

        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin([], $this->createAdminRole()));

        $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->setSkill('Accuracy', ['skill_bonus_per_level' => 10], [
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->setSkill('Dodge', [
                                            'skill_bonus_per_level' => 10,
                                        ])
                                        ->setSkill('Looting', [
                                            'skill_bonus_per_level' => 0,
                                        ])
                                        ->getCharacter();
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
        $this->seed(GameSkillsSeeder::class);

        $user = $this->createUser();

        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin([], $this->createAdminRole()));

        $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->setSkill('Accuracy', ['skill_bonus_per_level' => 10], [
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->setSkill('Dodge', [
                                            'skill_bonus_per_level' => 10,
                                        ])
                                        ->setSkill('Looting', [
                                            'skill_bonus_per_level' => 0,
                                        ])
                                        ->getCharacter();
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
        $this->seed(GameSkillsSeeder::class);

        $user = $this->createUser();

        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin([], $this->createAdminRole()));

        $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->setSkill('Accuracy', ['skill_bonus_per_level' => 10], [
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->setSkill('Dodge', [
                                            'skill_bonus_per_level' => 10,
                                        ])
                                        ->setSkill('Looting', [
                                            'skill_bonus_per_level' => 0,
                                        ])
                                        ->getCharacter();
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
        $this->seed(GameSkillsSeeder::class);

        $user = $this->createUser();

        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin([], $this->createAdminRole()));

        $skill = $this->createGameSkill();

        $monster->skills()->create([
            'monster_id' => $monster->id,
            'game_skill_id' => $skill->id,
            'level' => 1,
            'xp_max' => 999,
        ]);

        $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->setSkill('Accuracy', ['skill_bonus_per_level' => 10], [
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->setSkill('Dodge', [
                                            'skill_bonus_per_level' => 10,
                                        ])
                                        ->setSkill('Looting', [
                                            'skill_bonus_per_level' => 0,
                                        ])
                                        ->setSkill($skill->name, [
                                            'skill_bonus_per_level' => 0
                                        ])
                                        ->getCharacter();

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->set('for', 'all')
          ->call('validateInput', 'nextStep', 2);
          

        // Assert skill was applied:
        $this->assertEquals(1, $character->refresh()->skills()->where('game_skill_id', $skill->id)->count());
        $this->assertEquals(1, $monster->refresh()->skills()->where('game_skill_id', $skill->id)->count());
    }

    public function testAssignToClasses() {
        $this->seed(GameSkillsSeeder::class);

        $user = $this->createUser();

        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin([], $this->createAdminRole()));

        $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->setSkill('Accuracy', ['skill_bonus_per_level' => 10], [
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->setSkill('Dodge', [
                                            'skill_bonus_per_level' => 10,
                                        ])
                                        ->setSkill('Looting', [
                                            'skill_bonus_per_level' => 0,
                                        ])
                                        ->getCharacter();
        $skill = $this->createGameSkill();

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->set('for', 'select-class')
          ->set('selectedClass', $character->game_class_id)
          ->call('validateInput', 'nextStep', 2);

        $this->assertNotNull($character->skills->where('game_skill_id', $skill->id)->first());
    }

    public function testDontAssignToClassesWhenClassesHaveSkill() {
        $this->seed(GameSkillsSeeder::class);

        $user = $this->createUser();

        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin([], $this->createAdminRole()));

        $skill = $this->createGameSkill();

        $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->setSkill('Accuracy', ['skill_bonus_per_level' => 10], [
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->setSkill('Dodge', [
                                            'skill_bonus_per_level' => 10,
                                        ])
                                        ->setSkill('Looting', [
                                            'skill_bonus_per_level' => 0,
                                        ])
                                        ->setSkill($skill->name, [
                                            'skill_bonus_per_level' => 0
                                        ])
                                        ->getCharacter();
        

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->set('for', 'select-class')
          ->set('selectedClass', $character->game_class_id)
          ->call('validateInput', 'nextStep', 2);

        $this->assertEquals(1, $character->skills->where('game_skill_id', $skill->id)->count());
    }

    public function testAssignToAllWhenUserIsLoggedIn() {
        $this->seed(GameSkillsSeeder::class);

        $user = $this->createUser();

        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin([], $this->createAdminRole()));

        $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->setSkill('Accuracy', ['skill_bonus_per_level' => 10], [
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->setSkill('Dodge', [
                                            'skill_bonus_per_level' => 10,
                                        ])
                                        ->setSkill('Looting', [
                                            'skill_bonus_per_level' => 0,
                                        ])
                                        ->getCharacter();
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
        $this->seed(GameSkillsSeeder::class);

        $user = $this->createUser();

        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin([], $this->createAdminRole()));

        $character = (new CharacterSetup)->setupCharacter($user, ['can_move' => false])
                                        ->setSkill('Accuracy', ['skill_bonus_per_level' => 10], [
                                            'xp_towards' => 10,
                                            'currently_training' => true
                                        ])
                                        ->setSkill('Dodge', [
                                            'skill_bonus_per_level' => 10,
                                        ])
                                        ->setSkill('Looting', [
                                            'skill_bonus_per_level' => 0,
                                        ])
                                        ->getCharacter();

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
        $this->seed(GameSkillsSeeder::class);

        $monster = $this->createMonster();

        $this->actingAs($this->createAdmin([], $this->createAdminRole()));

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
        $this->seed(GameSkillsSeeder::class);

        $this->actingAs($this->createAdmin([], $this->createAdminRole()));

        $skill = $this->createGameSkill();

        Mail::fake();

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->set('for', 'select-monsters')
          ->set('selectedMonsters', [9999])
          ->call('validateInput', 'nextStep', 2);

        // No monster exists for this id.
        // An email should be sent.
    }

    public function testInitialSkillIsArray() {
        $skill = $this->createGameSkill();
        
        Livewire::test(SkillModifiers::class, ['skill' => $skill->toArray()])->assertSet('skill.name', $skill->name);
    }
}
