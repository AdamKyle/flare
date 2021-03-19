<?php

namespace Tests\Feature\Admin\Charts;

use App\Admin\Mail\GenericMail;
use Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class BattleSimmulationChartTest extends TestCase {

    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateItem,
        CreateClass,
        CreateRace,
        CreateGameMap,
        CreateGameSkill,
        CreateMonster;

    private $user;

    public function setUp(): void {
        parent::setUp();

        $this->user = $this->createAdmin([], $this->createAdminRole());
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->user = null;
    }

    public function testGetResultsWhereEveryOneLives() {
        $this->createBattleResults();

        $response = $this->actingAs($this->user)->json('GET', route('charts.battle_simmulation_chart', [
            'monsterId' => 1,
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(1, $content->datasets[0]->values[0]);
    }

    public function testGetResultsWhereEveryOneDies() {
        $this->createBattleResults(1, [
            'str'          => 1000,
            'dur'          => 1000,
            'dex'          => 1000,
            'chr'          => 1000,
            'int'          => 1000,
            'ac'           => 1000,
            'health_range' => '1-8',
            'attack_range' => '1-6',
        ]);

        $response = $this->actingAs($this->user)->json('GET', route('charts.battle_simmulation_chart', [
            'monsterId' => 1,
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(1, $content->datasets[0]->values[1]);
    }

    public function testGetResultsWhereTookTooLong() {
        $this->createBattleResults(1, [
            'str'          => 10,
            'dur'          => 10,
            'dex'          => 10,
            'chr'          => 10,
            'int'          => 10,
            'ac'           => 10,
            'health_range' => '100-800',
            'attack_range' => '100-600',
        ]);

        $response = $this->actingAs($this->user)->json('GET', route('charts.battle_simmulation_chart', [
            'monsterId' => 1,
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(1, $content->datasets[0]->values[2]);
    }

    protected function createBattleResults(int $times = 1, array $monsterOptions = []) {
        Mail::fake();

        $this->createRace();
        $this->createClass();
        $this->createGameSkill(['name' => 'Accuracy']);
        $this->createGameSkill(['name' => 'Dodge']);
        $this->createGameSkill(['name' => 'Looting']);
        $this->createGameMap();
        $this->createItem();

        $this->actingAs($this->user)->post(route('admin.character.modeling.generate'));
        
        $this->actingAs($this->user)->visit(route('monsters.list'))->post(route('admin.character.modeling.test'), [
            'model_id' => $this->createMonster($monsterOptions)->id,
            'type' => 'monster',
            'characters' => [1],
            'character_levels' => '1',
            'total_times' => $times,
        ]);

        Mail::assertSent(GenericMail::class);
    }
}