<?php

namespace Tests\Unit\Game\Exploration\Jobs;

use DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Values\AttackTypeValue;
use App\Game\Exploration\Jobs\Exploration;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateItemAffix;
use Tests\Traits\CreateMonster;

class ExplorationTest extends TestCase
{
    use RefreshDatabase, CreateMonster, CreateCharacterAutomation, CreateItemAffix;

    private $character;

    private $processAttackAutomation;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation();

        $this->createItemAffix(); // when random items are generated.
    }

    public function testAutomationJob() {
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

        $automation = $this->createExploringAutomation([
            'character_id' => $character->id,
            'monster_id'   => $monster->id,
            'started_at'   => now(),
            'completed_at' => now()->addSeconds(2),
            'attack_type'  => AttackTypeValue::ATTACK
        ]);

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $character->user->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        Exploration::dispatch($character, $automation->id, AttackTypeValue::ATTACK);

        $character = $character->refresh();

        $this->assertTrue($character->currentAutomations->isEmpty());
    }

    public function testAutomationJobMovingDownTheList() {
        $character = $this->character->equipStrongGear()->updateSkill('Accuracy', [
            'level' => 999
        ])->assignFactionSystem()->getCharacter();

        $monster   = $this->createMonster([
            'health_range' => '1-1',
            'dex'          => 0,
            'agi'          => 0,
            'attack_range' => '1-1',
            'ac'           => 0,
            'xp'           => 150,
        ]);

        // Creates the next monster
        $this->createMonster([
            'health_range' => '1-1',
            'dex'          => 0,
            'agi'          => 0,
            'attack_range' => '1-1',
            'ac'           => 0,
            'xp'           => 10,
        ]);

        $automation = $this->createExploringAutomation([
            'character_id'                 => $character->id,
            'monster_id'                   => $monster->id,
            'started_at'                   => now(),
            'completed_at'                 => now()->addSeconds(2),
            'attack_type'                  => AttackTypeValue::ATTACK,
            'move_down_monster_list_every' => 1,
        ]);

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $character->user->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        Exploration::dispatch($character, $automation->id, AttackTypeValue::ATTACK);

        $character = $character->refresh();

        $this->assertTrue($character->currentAutomations->isEmpty());
    }

    public function testAutomationJobTookTooLong() {
        $character = $this->character->assignFactionSystem()->getCharacter();

        $monster   = $this->createMonster([
            'health_range' => '999-99999',
            'dex'          => 0,
            'agi'          => 0,
            'attack_range' => '1-1',
            'ac'           => 0,
        ]);

        $automation = $this->createExploringAutomation([
            'character_id' => $character->id,
            'monster_id'   => $monster->id,
            'started_at'   => now(),
            'completed_at' => now()->addSeconds(2),
            'attack_type'  => AttackTypeValue::ATTACK
        ]);

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $character->user->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        Exploration::dispatch($character, $automation->id, AttackTypeValue::ATTACK);

        $this->assertTrue($character->currentAutomations->isEmpty());
    }

    public function testAutomAtionBailWhenNoAutomation() {
        $character = $this->character->getCharacter();

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $character->user->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        Exploration::dispatch($character, 16, AttackTypeValue::ATTACK);

        $this->assertTrue(true);
    }

    public function testAutomationBailWhenNotOnline() {
        $character = $this->character->getCharacter();

        $monster   = $this->createMonster([
            'health_range' => '1-1',
            'dex'          => 0,
            'agi'          => 0,
            'attack_range' => '1-1',
            'ac'           => 0,
        ]);

        $automation = $this->createExploringAutomation([
            'character_id' => $character->id,
            'monster_id'   => $monster->id,
            'started_at'   => now(),
            'completed_at' => now()->addSeconds(2),
            'attack_type'  => AttackTypeValue::ATTACK
        ]);

        Exploration::dispatch($character, $automation->id, AttackTypeValue::ATTACK);

        $this->assertTrue($character->currentAutomations->isEmpty());
    }

    public function testAutomationBailWhenMaxTimeIsUp() {
        $character = $this->character->getCharacter();

        $monster   = $this->createMonster([
            'health_range' => '1-1',
            'dex'          => 0,
            'agi'          => 0,
            'attack_range' => '1-1',
            'ac'           => 0,
        ]);

        $automation = $this->createExploringAutomation([
            'character_id' => $character->id,
            'monster_id'   => $monster->id,
            'started_at'   => now()->subHours(25),
            'completed_at' => now()->addHours(50),
            'attack_type'  => AttackTypeValue::ATTACK
        ]);

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $character->user->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);


        Exploration::dispatch($character, $automation->id, AttackTypeValue::ATTACK);

        $character = $character->refresh();

        $this->assertTrue($character->currentAutomations->isEmpty());

        $this->assertTrue($character->is_attack_automation_locked);
    }
}
