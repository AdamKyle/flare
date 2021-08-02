<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Monsters\Partials;

use App\Flare\Models\GameSkill;
use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Monsters\Partials\Skills;
use Tests\TestCase;
use Tests\Traits\CreateMonster;

class SkillsTest extends TestCase
{
    use RefreshDatabase, CreateMonster;

    public function setUp(): void {
        parent::setUp();
    }

    public function testTheComponentLoads() {
        $monster = $this->createMonster()->load('skills');

        Livewire::test(Skills::class, ['monster' => $monster])->assertSee('Please select');
    }

    public function testTheComponentCallsUpdate() {
        $monster = $this->createMonster()->load('skills');

        Livewire::test(Skills::class)->call('update', $monster->id)->assertSet('monster.name', $monster->name);
    }

    public function testEmitWithOutSaving() {
        $monster = $this->createMonster()->load('skills');

        Livewire::test(Skills::class, ['monster' => $monster])->call('validateInput', 'nextStep', 2)->assertEmitted('nextStep', 2, true);
    }

    public function testMonsterSkillShouldNotSet() {
        $monster = $this->createMonster()->load('skills');

        Livewire::test(Skills::class, ['monster' => $monster])->call('editSkill')
                                                                               ->assertSet('monsterSkill', null);
    }
}
