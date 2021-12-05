<?php

namespace Tests\Unit\Game\Automation\Services;

use App\Flare\Values\AttackTypeValue;
use App\Game\Automation\Services\ProcessAttackAutomation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateMonster;

class ProcessAttackAutomationTest extends TestCase
{
    use RefreshDatabase, CreateMonster, CreateCharacterAutomation, CreateItemAffix;

    private $character;

    private $processAttackAutomation;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()
                                                   ->givePlayerLocation()
                                                   ->assignFactionSystem();


        $this->processAttackAutomation = resolve(ProcessAttackAutomation::class);

        $this->createItemAffix(); // when random items are generated.
    }

    public function testProcessAndLive() {
        $character = $this->character->equipStrongGear()->updateSkill('Accuracy', [
            'level' => 999
        ])->assignFactionSystem()->getCharacter();

        $monster   = $this->createMonster([
            'health_range' => '1-1',
            'dex'          => 0,
            'agi'          => 0,
            'attack_range' => '1-1',
            'ac'           => 0,
        ]);

        $automation = $this->createAttackAutomation([
            'character_id' => $character->id,
            'monster_id'   => $monster->id,
            'started_at'   => now(),
            'completed_at' => now()->addSeconds(2),
            'attack_type'  => AttackTypeValue::ATTACK
        ]);

        $time = $this->processAttackAutomation->processFight($automation, $character, AttackTypeValue::ATTACK);

        $this->assertGreaterThan(0, $time);
    }

    public function testProcessAndTookTooLong() {
        $character = $this->character->getCharacter();

        $monster   = $this->createMonster([
            'health_range' => '1-1',
            'dex'          => 0,
            'agi'          => 0,
            'attack_range' => '1-1',
            'ac'           => 0,
        ]);

        $automation = $this->createAttackAutomation([
            'character_id' => $character->id,
            'monster_id'   => $monster->id,
            'started_at'   => now(),
            'completed_at' => now()->addSeconds(2),
            'attack_type'  => AttackTypeValue::ATTACK
        ]);

        $time = $this->processAttackAutomation->processFight($automation, $character, AttackTypeValue::ATTACK);

        $this->assertEquals(0, $time);

        $this->assertEmpty($character->currentAutomations->toArray());
    }

    public function testProcessAndDied() {
        $character = $this->character->getCharacter();

        $monster   = $this->createMonster([
            'health_range' => '999-9999999999',
            'dex'          => 999999,
            'agi'          => 9999,
            'attack_range' => '9999-999999',
            'ac'           => 999999,
            'accuracy'     => 3.9
        ]);

        $automation = $this->createAttackAutomation([
            'character_id' => $character->id,
            'monster_id'   => $monster->id,
            'started_at'   => now(),
            'completed_at' => now()->addSeconds(2),
            'attack_type'  => AttackTypeValue::ATTACK
        ]);

        $time = $this->processAttackAutomation->processFight($automation, $character, AttackTypeValue::ATTACK);

        $this->assertEquals(0, $time);
        $character = $character->refresh();

        $this->assertTrue($character->is_dead);
        $this->assertEmpty($character->currentAutomations->toArray());
    }
}
