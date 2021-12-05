<?php

namespace Tests\Feature\Automation;

use App\Flare\Values\AttackTypeValue;
use App\Game\Automation\Jobs\AttackAutomation;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateCharacterAutomation;
use Tests\Traits\CreateMonster;

class AttackAutomationControllerApiTest extends TestCase
{
    use RefreshDatabase, CreateMonster, CreateCharacterAutomation;

    private $character;

    private $monster;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())
                                ->createBaseCharacter()
                                ->givePlayerLocation();

        $this->monster = $this->createMonster();
    }

    public function testCanGetAttackAutomation() {
        $character = $this->character->getCharacter(false);

        $this->createAttackAutomation([
            'character_id' => $character->id,
            'monster_id'   => $this->monster->id,
            'started_at'   => now(),
            'completed_at' => now()->addSeconds(2),
            'attack_type'  => AttackTypeValue::CAST
        ]);

        $response = $this->actingAs($character->user)->getJson(route('attack.automation.index', [
            'character'           => $character->id
        ]))->response;

        $this->assertEquals(200, $response->status());
    }

    public function testHasNoAutomation() {
        $character = $this->character->getCharacter(false);

        $response = $this->actingAs($character->user)->getJson(route('attack.automation.index', [
            'character'           => $character->id
        ]))->response;

        $this->assertEquals(200, $response->status());
        $this->assertEmpty(json_decode($response->content())->automation);
    }

    public function testCanDoAutoAttack() {
        Queue::fake();

        $character = $this->character->getCharacter(false);

        $accuracySkill = $character->skills->filter(function($skill) {
            return $skill->baseSkill->name === 'Accuracy';
        })->first();

        $response = $this->actingAs($character->user)->postJson(route('attack.automation.start', [
            'character' => $character->id
        ]), [
            'skill_id'                 => $accuracySkill->id,
            'xp_towards'               => 0.10,
            'auto_attack_length'       => 1,
            'move_down_the_list_every' => 10,
            'selected_monster_id'      => $this->monster->id,
            'attack_type'              => 'attack',
        ])->response;

        $this->assertEquals(200, $response->status());

        Queue::assertPushed(AttackAutomation::class);
    }

    public function testCanStopAutomationAttack() {
        $character = $this->character->getCharacter(false);

        $automation = $this->createAttackAutomation([
            'character_id' => $character->id,
            'monster_id'   => $this->monster->id,
            'started_at'   => now(),
            'completed_at' => now()->addSeconds(2),
            'attack_type'  => AttackTypeValue::CAST
        ]);

        $this->assertFalse($character->refresh()->currentAutomations->isEmpty());

        $response = $this->actingAs($character->user)->postJson(route('attack.automation.stop', [
            'characterAutomation' => $automation->id,
            'character'           => $character->id
        ]))->response;

        $this->assertEquals(200, $response->status());

        $this->assertTrue($character->refresh()->currentAutomations->isEmpty());
    }

    public function testCannotStopAutomationYouDoNotOwn() {
        $character      = $this->character->getCharacter(false);
        $otherCharacter = (new CharacterFactory())->createBaseCharacter()->getCharacter(false);

        $automation = $this->createAttackAutomation([
            'character_id' => $otherCharacter->id,
            'monster_id'   => $this->monster->id,
            'started_at'   => now(),
            'completed_at' => now()->addSeconds(2),
            'attack_type'  => AttackTypeValue::CAST
        ]);

        $this->assertFalse($otherCharacter->refresh()->currentAutomations->isEmpty());

        $response = $this->actingAs($character->user)->postJson(route('attack.automation.stop', [
            'characterAutomation' => $automation->id,
            'character'           => $character->id
        ]))->response;

        $this->assertEquals(422, $response->status());

        $this->assertFalse($otherCharacter->refresh()->currentAutomations->isEmpty());
    }
}
