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
        ])->set('for', 'select-monster')
          ->set('monster', $monster->id)
          ->call('validateInput', 'nextStep', 2);
          

        // Assert skill was applied:
        $this->assertNull($character->refresh()->skills()->where('game_skill_id', $skill->id)->first());
        $this->assertNotNull($monster->refresh()->skills()->where('game_skill_id', $skill->id)->first());
    }

    public function testFailToAssignToUnknownMonster() {
        $this->seed(GameSkillsSeeder::class);

        $this->actingAs($this->createAdmin([], $this->createAdminRole()));

        $skill = $this->createGameSkill();

        Mail::fake();

        Livewire::test(SkillModifiers::class, [
            'skill' => $skill,
        ])->set('for', 'select-monster')
          ->set('monster', 1)
          ->call('validateInput', 'nextStep', 2);

        Mail::assertSent(function (GenericMail $mail) {
            return $mail->genericMessage === 'Something went wrong trying to assign the skills: Monster not found for id: 1';
        }, 1);
    }

    public function testInitialSkillIsArray() {
        $skill = $this->createGameSkill();
        
        Livewire::test(SkillModifiers::class, ['skill' => $skill->toArray()])->assertSet('skill.name', $skill->name);
    }
}
