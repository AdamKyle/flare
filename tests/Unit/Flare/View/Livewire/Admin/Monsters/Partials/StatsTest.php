<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Monsters\Partials;

use App\Flare\Models\Monster;
use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Monsters\Partials\Stats;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateGameSkill;

class StatsTest extends TestCase
{
    use RefreshDatabase, CreateMonster, CreateGameSkill, CreateGameMap;

    public function setUp(): void {
        parent::setUp();

        $this->createGameSkill();
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
        $map = $this->createGameMap();

        Livewire::test(Stats::class)->set('monster.name', 'Sample')
                                    ->set('monster.str', 10)
                                    ->set('monster.dur', 10)
                                    ->set('monster.dex', 10)
                                    ->set('monster.chr', 10)
                                    ->set('monster.int', 10)
                                    ->set('monster.agi', 10)
                                    ->set('monster.focus', 10)
                                    ->set('monster.ac', 'ac')
                                    ->set('monster.damage_stat', 'str')
                                    ->set('monster.xp', 100)
                                    ->set('monster.drop_check', 0.10)
                                    ->set('monster.gold', 100)
                                    ->set('monster.health_range', '10-20')
                                    ->set('monster.attack_range', '10-20')
                                    ->set('monster.max_level', 10)
                                    ->set('monster.game_map_id', $map->id)
                                    ->call('validateInput', 'nextStep', 2);

        // Assert Monster was created:
        $this->assertNotNull(Monster::where('name', 'Sample')->first());
    }

    public function testUpdateMonster() {
        $monster = $this->createMonster();

        $map = $this->createGameMap();

        Livewire::test(Stats::class, ['initialMonster' => $monster])
                                    ->set('monster.name', 'Sample')
                                    ->set('monster.str', $monster->str)
                                    ->set('monster.dur', $monster->dur)
                                    ->set('monster.dex', $monster->dex)
                                    ->set('monster.chr', $monster->chr)
                                    ->set('monster.int', $monster->int)
                                    ->set('monster.agi', $monster->agi)
                                    ->set('monster.focus', $monster->focus)
                                    ->set('monster.ac', $monster->ac)
                                    ->set('monster.damage_stat', 'str')
                                    ->set('monster.xp', $monster->xp)
                                    ->set('monster.drop_check', $monster->drop_check)
                                    ->set('monster.gold', $monster->gold)
                                    ->set('monster.health_range', $monster->health_range)
                                    ->set('monster.attack_range', $monster->attack_range)
                                    ->set('monster.max_level', $monster->max_level)
                                    ->set('monster.game_map_id', $map->id)
                                    ->call('validateInput', 'nextStep', 2);

        // Assert Monster was updated:
        $this->assertNotNull(Monster::where('name', 'Sample')->first());
    }

    public function testInitialMonsterIsArray() {
        $monster = $this->createMonster()->load('skills');

        Livewire::test(Stats::class, ['monster' => $monster->toArray()])->assertSet('monster.name', $monster->name);
    }
}
