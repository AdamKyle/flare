<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Skills\Partials;

use App\Game\Skills\Values\SkillTypeValue;
use Livewire;
use App\Flare\Models\GameSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Skills\Partials\SkillDetails;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;

class SkillDetailsTest extends TestCase
{
    use RefreshDatabase, CreateGameSkill;

    public function testTheComponentLoads() {
        Livewire::test(SkillDetails::class)->assertSee('Name')->assertDontSee('Previous');
    }

    public function testValidationFails() {
        Livewire::test(SkillDetails::class)->call('validateInput', 'nextStep', 2)->assertHasErrors([
            'skill.name' => 'required'
        ]);
    }

    public function testValidationFailsWhenMaxLevelIsBelowZero() {
        Livewire::test(SkillDetails::class)
            ->set('skill.name', 'sample')
            ->set('skill.description', 'sample')
            ->set('skill.max_level', -5)
            ->set('skill.type', SkillTypeValue::TRAINING)
            ->call('validateInput', 'nextStep', 2)
            ->assertHasErrors('gameSkill.max_level');
    }

    public function testCreateSkill() {
        Livewire::test(SkillDetails::class)
            ->set('skill.name', 'Sample')
            ->set('skill.max_level', 100)
            ->set('skill.description', 'test')
            ->set('skill.type', SkillTypeValue::TRAINING)
            ->call('validateInput', 'nextStep', 2);

        $gameSkill = GameSkill::where('name', 'Sample');

        $this->assertNotNull($gameSkill);
    }

    public function testUpdateGameSkill() {
        $skill = $this->createGameSkill();

        Livewire::test(SkillDetails::class, ['skill' => $skill])
                                        ->set('skill.name', 'Apple Sauce')
                                        ->set('skill.description', $skill->description)
                                        ->set('skill.max_level', $skill->max_level)
                                        ->set('skill.type', SkillTypeValue::TRAINING)
                                        ->call('validateInput', 'nextStep', 2);

        // Assert skill was updated:
        $this->assertNotNull(GameSkill::where('name', 'Apple Sauce')->first());
    }
}
