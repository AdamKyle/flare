<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Monsters\Partials;

use App\Flare\Models\Monster;
use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Monsters\Partials\Stats;
use Database\Seeders\GameSkillsSeeder;
use Tests\TestCase;
use Tests\Traits\CreateMonster;

class StatsTest extends TestCase
{
    use RefreshDatabase, CreateMonster;

    public function setUp(): void {
        parent::setUp();

        $this->seed(GameSkillsSeeder::class);
    }

    public function testTheComponentLoads() {
        Livewire::test(Stats::class)->assertSee('Name')->assertDontSee('Previous');
    }

    public function testValidationFails() {
        Livewire::test(Stats::class)->call('validateInput', 'nextStep', 2)->assertHasErrors([
            'monster.name' => 'required'
        ]);
    }

    public function testCreateValidMonster() {
        Livewire::test(Stats::class)->set('monster.name', 'Sample')
                                    ->set('monster.str', 10)
                                    ->set('monster.dur', 10)
                                    ->set('monster.dex', 10)
                                    ->set('monster.chr', 10)
                                    ->set('monster.int', 10)
                                    ->set('monster.ac', 'ac')
                                    ->set('monster.damage_stat', 'str')
                                    ->set('monster.xp', 100)
                                    ->set('monster.drop_check', 0.10)
                                    ->set('monster.gold', 100)
                                    ->set('monster.health_range', '10-20')
                                    ->set('monster.attack_range', '10-20')
                                    ->set('monster.max_level', 10)
                                    ->call('validateInput', 'nextStep', 2);

        // Assert Monster was created:
        $this->assertNotNull(Monster::where('name', 'Sample')->first());
    }

    public function testUpdateMonster() {
        $monster = $this->createMonster();

        Livewire::test(Stats::class, ['initialMonster' => $monster])
                                    ->set('monster.name', 'Sample')
                                    ->set('monster.str', $monster->str)
                                    ->set('monster.dur', $monster->dur)
                                    ->set('monster.dex', $monster->dex)
                                    ->set('monster.chr', $monster->chr)
                                    ->set('monster.int', $monster->int)
                                    ->set('monster.ac', $monster->ac)
                                    ->set('monster.damage_stat', 'str')
                                    ->set('monster.xp', $monster->xp)
                                    ->set('monster.drop_check', $monster->drop_check)
                                    ->set('monster.gold', $monster->gold)
                                    ->set('monster.health_range', $monster->health_range)
                                    ->set('monster.attack_range', $monster->attack_range)
                                    ->set('monster.max_level', $monster->max_level)
                                    ->call('validateInput', 'nextStep', 2);

        // Assert Monster was updated:
        $this->assertNotNull(Monster::where('name', 'Sample')->first());
    }

    public function testInitialMonsterIsArray() {
        $monster = $this->createMonster()->load('skills');
        
        Livewire::test(Stats::class, ['monster' => $monster->toArray()])->assertSet('monster.name', $monster->name);
    }
}
