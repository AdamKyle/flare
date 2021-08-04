<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Classes\Partials;

use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\GameClass;
use App\Flare\View\Livewire\Admin\Classes\Partials\GameClass as GameClassDetails;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateUser;

class GameClassTest extends TestCase
{
    use RefreshDatabase, CreateClass, CreateUser;

    public function testTheComponentLoads() {
        Livewire::test(GameClassDetails::class)->assertSee('Name');
    }

    public function testValidationFails() {
        Livewire::test(GameClassDetails::class)
            ->call('validateInput', 'nextStep', 2)
            ->assertHasErrors('gameClass.name')
            ->assertHasErrors('gameClass.damage_stat');
    }

    public function testCreateValidClass() {
        Livewire::test(GameClassDetails::class)
            ->call('validateInput', 'nextStep', 2)
            ->set('gameClass.name', 'Sample')
            ->set('gameClass.damage_stat', 'str')
            ->set('gameClass.to_hit_stat', 'str')
            ->call('validateInput', 'nextStep', 2);

        $this->assertTrue(!is_null(GameClass::where('name', 'Sample')->first()));
    }

    public function testChangeClassStats() {
        $gameClass = $this->createClass();

        Livewire::test(GameClassDetails::class, [
            'gameClass' => $gameClass
        ])
            ->call('validateInput', 'nextStep', 2)
            ->set('gameClass.name', 'New Class')
            ->set('gameClass.damage_stat', 'str')
            ->set('gameClass.str_mod', 100)
            ->call('validateInput', 'nextStep', 2);

        $gameClass = $gameClass->refresh();

        $this->assertEquals('New Class', $gameClass->name);
        $this->assertEquals(100, $gameClass->str_mod);
    }
}
