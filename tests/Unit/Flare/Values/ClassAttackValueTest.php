<?php

namespace Tests\Unit\Flare\Values;

use App\Flare\Values\ClassAttackValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class ClassAttackValueTest extends TestCase {

    use RefreshDatabase;

    public function testReturnProphetAttackData() {
        $character = (new CharacterFactory())->createBaseCharacter([], [
            'name' => 'Prophet'
        ])->getCharacter(false);

        $attackType = new ClassAttackValue($character);

        $data = $attackType->buildAttackData();

        $this->assertNotEmpty($data);
        $this->assertEquals(ClassAttackValue::PROPHET_HEALING, $data['type']);
    }

    public function testReturnHereticAttackData() {
        $character = (new CharacterFactory())->createBaseCharacter([], [
            'name' => 'Heretic'
        ])->getCharacter(false);

        $attackType = new ClassAttackValue($character);

        $data = $attackType->buildAttackData();

        $this->assertNotEmpty($data);
        $this->assertEquals(ClassAttackValue::HERETICS_DOUBLE_CAST, $data['type']);
    }

    public function testReturnThiefAttackData() {
        $character = (new CharacterFactory())->createBaseCharacter([], [
            'name' => 'Thief'
        ])->getCharacter(false);

        $attackType = new ClassAttackValue($character);

        $data = $attackType->buildAttackData();

        $this->assertNotEmpty($data);
        $this->assertEquals(ClassAttackValue::THIEVES_SHADOW_DANCE, $data['type']);
    }

    public function testReturnRangerAttackData() {
        $character = (new CharacterFactory())->createBaseCharacter([], [
            'name' => 'Ranger'
        ])->getCharacter(false);

        $attackType = new ClassAttackValue($character);

        $data = $attackType->buildAttackData();

        $this->assertNotEmpty($data);
        $this->assertEquals(ClassAttackValue::RANGER_TRIPLE_ATTACK, $data['type']);
    }

    public function testReturnVampireAttackData() {
        $character = (new CharacterFactory())->createBaseCharacter([], [
            'name' => 'Vampire'
        ])->getCharacter(false);

        $attackType = new ClassAttackValue($character);

        $data = $attackType->buildAttackData();

        $this->assertNotEmpty($data);
        $this->assertEquals(ClassAttackValue::VAMPIRE_THIRST, $data['type']);
    }
}
