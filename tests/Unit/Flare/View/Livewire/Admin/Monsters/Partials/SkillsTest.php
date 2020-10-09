<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Monsters\Partials;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Monsters\Partials\Skills;
use Database\Seeders\GameSkillsSeeder;
use Tests\TestCase;
use Tests\Traits\CreateMonster;

class SkillsTest extends TestCase
{
    use RefreshDatabase, CreateMonster;

    public function setUp(): void {
        parent::setUp();
        
        $this->seed(GameSkillsSeeder::class);
    }

    public function testTheComponentLoads() {
        $monster = $this->createMonster()->load('skills');

        Livewire::test(Skills::class, ['monster' => $monster->getAttributes()])->assertSee('Please select');
    }

    public function testEmitWithOutSaving() {
        $monster = $this->createMonster()->load('skills');

        Livewire::test(Skills::class, ['monster' => $monster->getAttributes()])->call('validateInput', 'nextStep', 2)->assertEmitted('nextStep', 2, true);
    }

    public function testEmitAfterSaving() {
        $monster = $this->createMonster()->load('skills');

        Livewire::test(Skills::class, ['monster' => $monster->getAttributes()])->set('selectedSkill', 1)
                                                                               ->call('editSkill')
                                                                               ->set('monsterSkill.level', 20)
                                                                               ->call('validateInput', 'nextStep', 2)->assertEmitted('nextStep', 2, true);
    
        $found = $monster->refresh()->skills()->where('level', 20)->first();

        $this->assertNotNull($found);
    }
    public function testMonsterSkillShouldNotSet() {
        $monster = $this->createMonster()->load('skills');

        Livewire::test(Skills::class, ['monster' => $monster->getAttributes()])->call('editSkill')
                                                                               ->assertSet('monsterSkill', null);
    }

    public function testSaveWasCalled() {
        $monster = $this->createMonster()->load('skills');
        
        Livewire::test(Skills::class, ['monster' => $monster->getAttributes()])->set('selectedSkill', 1)
                                                                               ->call('editSkill')
                                                                               ->set('monsterSkill.level', 10)
                                                                               ->call('save');

        $found = $monster->refresh()->skills()->where('level', 10)->first();

        $this->assertNotNull($found);
    }
}
