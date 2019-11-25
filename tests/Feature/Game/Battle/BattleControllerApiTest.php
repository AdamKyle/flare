<?php

namespace Tests\Feature\Game\Battle;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Flare\Events\ServerMessageEvent;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateSkill;

class BattleControllerApiTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateRace,
        CreateClass,
        CreateCharacter,
        CreateMonster,
        CreateSkill;

    private $user;

    private $character;

    private $monster;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->user      = null;
        $this->character = null;
        $this->monster   = null;
    }

    public function testCanGetActions() {
        $this->setUpCharacter();

        $response = $this->actingAs($this->user, 'api')
                         ->json('GET', '/api/actions', [
                             'user_id' => $this->user->id
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $content = json_decode($response->content());

        $this->assertNotEmpty($content->monsters);
        $this->assertNotEmpty($content->monsters[0]->skills);
        $this->assertEquals($this->character->name, $content->character->data->name);
    }

    public function testWhenNotLoggedInCannotGetActions() {
        $response = $this->json('GET', '/api/actions', [
                             'user_id' => 1
                         ])
                         ->response;

        $this->assertEquals(401, $response->status());
    }

    public function testBattleResultsCharacterIsDead() {
        Event::fake([ServerMessageEvent::class]);

        $this->setUpCharacter();

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->user->character->id, [
                             'is_character_dead' => true
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());
    }

    public function testBattleResultsMonsterIsDead() {
        $this->setUpCharacter();

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->user->character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $this->monster->id,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());
    }

    public function testBattleResultsMonsterIsDeadAndCharacterLevelUp() {
        $this->setUpCharacter([
            'xp' => 90,
            'level' => 1,
        ]);

        $this->character->refresh();

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $this->monster->id,
                         ])
                         ->response;

        $this->character->refresh();

        $this->assertEquals(200, $response->status());
        $this->assertEquals(2, $this->character->level);
    }

    public function testWhenNotLoggedInCannotAccessBattleResults() {
        $response = $this->json('POST', '/api/battle-results/1')
                         ->response;

        $this->assertEquals(401, $response->status());
    }

    protected function setUpCharacter(array $options = []) {
        $race = $this->createRace([
            'str_mod' => 3,
        ]);

        $class = $this->createClass([
            'dex_mod'     => 3,
            'damage_stat' => 'dex',
        ]);

        $this->user = $this->createUser();

        $this->character = $this->createCharacter([
            'name' => 'Sample',
            'user_id' => $this->user->id,
            'level' => isset($options['level']) ? $options['level'] : 1,
            'xp' => isset($options['xp']) ? $options['xp'] : 0
        ]);

        $this->monster = $this->createMonster();

        $this->createSkill([
            'monster_id' => $this->monster->id,
        ]);
    }
}
