<?php

namespace Tests\Feature\Game\Battle;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use App\Flare\Models\Character;
use App\Flare\Events\ServerMessageEvent;
use App\Game\Battle\Events\GoldRushCheckEvent;
use App\Game\Battle\Events\DropCheckEvent;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Events\ShowTimeOutEvent;
use App\Game\Battle\Events\UpdateCharacterEvent;
use App\Game\Battle\Events\UpdateTopBarEvent;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateDrops;
use Tests\Traits\CreateItem;
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
        CreateItem,
        CreateDrops,
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

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($content->monsters);
        $this->assertNotEmpty($content->monsters[0]->skills);
        $this->assertEquals($this->character->name, $content->character->data->name);
        $this->assertEquals(17, $content->character->data->attack);
    }

    public function testWhenNotLoggedInCannotGetActions() {
        $response = $this->json('GET', '/api/actions', [
                             'user_id' => 1
                         ])
                         ->response;

        $this->assertEquals(401, $response->status());
    }

    public function testBattleResultsCharacterIsDead() {
        Queue::Fake();

        Event::fake([ServerMessageEvent::class, UpdateTopBarEvent::class]);

        $this->setUpCharacter();

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->user->character->id, [
                             'is_character_dead' => true
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());
    }

    public function testBattleResultsMonsterIsDead() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            GoldRushCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarEvent::class,
        ]);

        $this->setUpCharacter();

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->user->character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $this->monster->id,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $this->character->refresh();

        $this->assertEquals(25, $this->character->gold);
    }

    public function testBattleResultsMonsterIsDeadAndCharacterLevelUp() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropCheckEvent::class,
            GoldRushCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarEvent::class,
        ]);

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
        $this->assertEquals(0, $this->character->xp);
    }

    public function testBattleResultsMonsterIsDeadAndCharacterGainedItem() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            GoldRushCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarEvent::class,
        ]);

        $this->setUpCharacter([
            'looting_level' => 100,
            'looting_bonus' => 100,
            'create_drop'   => true,
        ]);

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $this->monster->id,
                         ])
                         ->response;

        $this->character->refresh();

        $this->assertEquals(200, $response->status());
        $this->assertEquals(1, $this->character->level);
        $this->assertEquals(25, $this->character->gold);
        $this->assertEquals('Rusty Dagger', $this->character->inventory->slots->first()->item->name);
    }

    public function testBattleResultsMonsterIsDeadAndCharacterGainedGoldRush() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarEvent::class,
        ]);

        $this->setUpCharacter([
            'looting_level' => 100,
            'looting_bonus' => 100,
            'create_drop'   => true,
        ]);

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $this->monster->id,
                         ])
                         ->response;

        $this->character->refresh();

        $this->assertEquals(200, $response->status());
        $this->assertNotEquals(0, $this->character->gold);
    }

    public function testBattleResultsMonsterIsDeadCannotAttackAgain() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            GoldRushCheckEvent::class,
            ShowTimeOutEvent::class,
            UpdateTopBarEvent::class,
        ]);

        $this->setUpCharacter();

        $response = $this->actingAs($this->user, 'api')
                         ->json('POST', '/api/battle-results/' . $this->user->character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $this->monster->id,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $this->character->refresh();

        $this->assertFalse($this->character->can_attack);
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
            'xp' => isset($options['xp']) ? $options['xp'] : 0,
            'can_attack' => true,
        ]);

        $item = $this->createItem([
            'name'        => 'Rusty Dagger',
            'type'        => 'weapon',
            'base_damage' => '6'
        ]);

        $this->character->inventory()->create([
            'character_id' => $this->character->id,
        ]);

        $this->character->equippedItems()->create([
            'character_id' => $this->character->id,
            'item_id'      => $item->id,
            'type'         => 'left-hand',
        ]);

        $this->monster = $this->createMonster();

        $this->createSkill([
            'monster_id' => $this->monster->id,
        ]);

        $this->createSkill([
            'monster_id' => $this->monster->id,
        ]);

        if (isset($options['create_drop']) && $options['create_drop']) {
            $this->createDrops([
                'monster_id' => $this->monster->id,
                'item_id'    => $item->id
            ]);

            $this->createDrops([
                'monster_id' => $this->monster->id,
                'item_id'    => $item->id
            ]);
        }

        $this->createSkill([
            'character_id' => $this->character->id,
            'name' => 'Looting',
            'level' => isset($options['looting_level']) ? $options['looting_level'] : 1,
            'skill_bonus' => isset($options['looting_bonus']) ? $options['looting_bonus'] : 0,
        ]);
    }
}
