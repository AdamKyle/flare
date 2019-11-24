<?php

namespace Tests\Feature\Game\Battle;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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

    public function setUp(): void {
        parent::setUp();
    }

    public function testCanGetActions() {
        $race = $this->createRace([
            'str_mod' => 3,
        ]);

        $class = $this->createClass([
            'dex_mod'     => 3,
            'damage_stat' => 'dex',
        ]);

        $user = $this->createUser();

        $character = $this->createCharacter([
            'name' => 'Sample',
            'user_id' => $user->id,
        ]);

        $monster = $this->createMonster();

        $this->createSkill([
            'monster_id' => $monster->id,
        ]);

        $response = $this->actingAs($user, 'api')
                         ->json('GET', '/api/actions', [
                             'user_id' => $user->id
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $content = json_decode($response->content());

        $this->assertNotEmpty($content->monsters);
        $this->assertNotEmpty($content->monsters[0]->skills);
        $this->assertEquals($character->name, $content->character->data->name);
    }

    public function testWhenNotLoggedInCannotGetActions() {
        $response = $this->json('GET', '/api/actions', [
                             'user_id' => 1
                         ])
                         ->response;

        $this->assertEquals(401, $response->status());
    }
}
