<?php

namespace Tests\Feature\Game\Battle;

use App\Game\Battle\Values\MaxLevel;
use App\Game\Core\Values\LevelUpValue;
use Mockery;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateCharacter;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateMonster;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateSkill;
use Tests\Setup\Monster\MonsterFactory;
use Tests\Traits\CreateItemAffix;
use App\Game\Battle\Controllers\Api\BattleController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use App\Flare\Events\ServerMessageEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Game\Core\Events\GoldRushCheckEvent;
use App\Game\Core\Events\AttackTimeOutEvent;
use App\Game\Core\Events\CharacterIsDeadBroadcastEvent;
use App\Game\Core\Events\DropsCheckEvent;
use App\Game\Core\Events\ShowTimeOutEvent;
use App\Game\Core\Events\UpdateTopBarBroadcastEvent;

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
        CreateSkill,
        CreateItemAffix;

    private $user;

    private $character;

    private $monster;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->equipStartingEquipment();

        $this->monster   = (new MonsterFactory)->buildMonster();

        $this->createItemAffix([
            'name'                 => 'Sample',
            'base_damage_mod'      => '0.10',
            'type'                 => 'prefix',
            'description'          => 'Sample',
            'base_healing_mod'     => '0.10',
            'str_mod'              => '0.10',
            'dur_mod'              => '0.10',
            'dex_mod'              => '0.10',
            'chr_mod'              => '0.10',
            'int_mod'              => '0.10',
            'skill_name'           => null,
            'skill_training_bonus' => null,
        ]);

        $this->createItemAffix([
            'name'                 => 'Sample',
            'base_damage_mod'      => '0.10',
            'type'                 => 'suffix',
            'description'          => 'Sample',
            'base_healing_mod'     => '0.10',
            'str_mod'              => '0.10',
            'dur_mod'              => '0.10',
            'dex_mod'              => '0.10',
            'chr_mod'              => '0.10',
            'int_mod'              => '0.10',
            'skill_name'           => null,
            'skill_training_bonus' => null,
        ]);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
        $this->monster   = null;
    }

    public function testCanGetActions() {

        $user     = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('GET', '/api/actions', [
                             'user_id' => $user->id
                         ])
                         ->response;

        $content   = json_decode($response->content());
        $character = $this->character->getCharacter();

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($content->monsters);
        $this->assertNotEmpty($content->monsters[0]->skills);
        $this->assertEquals($character->name, $content->character->name);
        $this->assertEquals(7, $content->character->attack);
    }

    public function testCanGetActionsWithSkills() {

        $user     = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('GET', '/api/actions', [
                             'user_id' => $user->id
                         ])
                         ->response;

        $content   = json_decode($response->content());
        $character = $this->character->getCharacter();

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($content->monsters);
        $this->assertNotEmpty($content->monsters[0]->skills);
        $this->assertEquals($character->name, $content->character->name);
        $this->assertEquals(7, $content->character->attack);
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

        Event::fake([ServerMessageEvent::class, UpdateTopBarBroadcastEvent::class]);

        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/battle-results/' . $character->id, [
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
            UpdateTopBarBroadcastEvent::class,
        ]);

        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();
        $monster   = $this->monster->getMonster();

        $currentGold = $character->gold;

        $response = $this->actingAs($user)
                         ->json('POST', '/api/battle-results/' . $character->id, [
                             'is_defender_dead' => true,
                             'defender_type'    => 'monster',
                             'monster_id'       => $monster->id,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $this->assertTrue($currentGold !== $this->character->getCharacter()->gold);
    }

    public function testBattleResultsMonsterIsDeadNoXpMaxLevel() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            GoldRushCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
        ]);

        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();
        $monster   = $this->monster->getMonster();

        $character->update([
            'level' => MaxLevel::MAX_LEVEL,
            'xp'    => 0,
        ]);

        $character = $character->refresh();

        $currentGold = $character->gold;

        $response = $this->actingAs($user)
            ->json('POST', '/api/battle-results/' . $character->id, [
                'is_defender_dead' => true,
                'defender_type'    => 'monster',
                'monster_id'       => $monster->id,
            ])
            ->response;

        $this->assertEquals(200, $response->status());

        $this->assertTrue($currentGold !== $this->character->getCharacter()->gold);
        $this->assertEquals(0, $this->character->getCharacter()->xp);
    }

    public function testBattleResultsWhenCharacterCannotAttack() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            GoldRushCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
        ]);

        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();
        $monster   = $this->monster->getMonster();

        $character->update([
            'can_attack' => false,
        ]);

        $character = $character->refresh();

        $response = $this->actingAs($user)
            ->json('POST', '/api/battle-results/' . $character->id, [
                'is_defender_dead' => true,
                'defender_type'    => 'monster',
                'monster_id'       => $monster->id,
            ])
            ->response;

        $this->assertEquals(429, $response->status());
    }

    public function testBattleResultsWhenCharacterAlreadyDead() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            GoldRushCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
        ]);

        $user      = $this->character->getUser();
        $character = $this->character->getCharacter();
        $monster   = $this->monster->getMonster();


        $character->update([
            'is_dead' => true,
        ]);

        $character = $character->refresh();

        $response = $this->actingAs($user)
            ->json('POST', '/api/battle-results/' . $character->id, [
                'is_defender_dead' => true,
                'defender_type'    => 'monster',
                'monster_id'       => $monster->id,
            ])
            ->response;

        $this->assertEquals(422, $response->status());
    }

    public function testBattleResultsMonsterIsDeadAndCharacterLevelUp() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            GoldRushCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
        ]);

        $character = $this->character->updateCharacter(['xp' => 99])->getCharacter();
        $user      = $this->character->getUser();
        $monster   = $this->monster->getMonster();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/battle-results/' . $character->id, [
                             'is_defender_dead' => true,
                             'defender_type'    => 'monster',
                             'monster_id'       => $monster->id,
                         ])
                         ->response;

        $character = $this->character->getCharacter();

        $this->assertEquals(200, $response->status());
        $this->assertEquals(2, $character->level);
        $this->assertEquals(0, $character->xp);
    }

    public function testBattleResultsMonsterIsDeadAndCharacterGainedItem() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            GoldRushCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
        ]);

        $character   = $this->character->updateSkill('Looting', ['level' => 100])->getCharacter();
        $user        = $this->character->getUser();
        $monster     = $this->monster->getMonster();

        $currentGold = $character->gold;

        $response = $this->actingAs($user)
                         ->json('POST', '/api/battle-results/' . $character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $monster->id,
                         ])
                         ->response;

        $character = $this->character->getCharacter();

        $this->assertEquals(200, $response->status());
        $this->assertTrue($currentGold !== $character->gold);
        $this->assertTrue(count($character->inventory->slots) > 1);
    }

    public function testBattleResultsMonsterIsDeadAndCharacterGainedQuestItem() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            GoldRushCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
        ]);

        $character   = $this->character->updateSkill('Looting', ['level' => 10000000])->getCharacter();
        $user        = $this->character->getUser();
        $monster     = $this->monster->getMonster();

        $item = $this->createItem([
            'name' => 'quest item',
            'type' => 'quest',
        ]);


        $monster->update([
            'quest_item_id' => $item->id,
            'quest_item_drop_chance' => 1.00,
        ]);

        $monster = $monster->refresh();

        $currentGold = $character->gold;

        $response = $this->actingAs($user)
            ->json('POST', '/api/battle-results/' . $character->id, [
                'is_defender_dead' => true,
                'defender_type' => 'monster',
                'monster_id' => $monster->id,
            ])
            ->response;


        $character = $character->refresh();

        $found = $character->inventory->slots->filter(function($slot) use ($item) {
            return $slot->item->name === $item->name;
        })->all();

        $this->assertEquals(200, $response->status());
        $this->assertTrue($currentGold !== $character->gold);
        $this->assertTrue(count($character->inventory->slots) > 1);
        $this->assertNotEmpty($found);
    }

    public function testBattleResultsMonsterIsDeadAndCharacterDidNotGainQuestItem() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            GoldRushCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
        ]);

        $character   = $this->character->updateSkill('Looting', ['level' => 0])->getCharacter();
        $user        = $this->character->getUser();
        $monster     = $this->monster->getMonster();


        $item = $this->createItem([
            'name' => 'quest item',
            'type' => 'quest',
        ]);


        $monster->update([
            'quest_item_id' => $item->id,
            'quest_item_drop_chance' => 0.0,
            'drop_check' => 0.0,
        ]);

        $monster = $monster->refresh();

        $currentGold = $character->gold;

        $response = $this->actingAs($user)
            ->json('POST', '/api/battle-results/' . $character->id, [
                'is_defender_dead' => true,
                'defender_type' => 'monster',
                'monster_id' => $monster->id,
            ])
            ->response;

        $character = $character->refresh();

        $found = $character->inventory->slots->filter(function($slot) use ($item) {
            return $slot->item->name === $item->name;
        })->all();

        $this->assertEquals(200, $response->status());
        $this->assertTrue($currentGold !== $character->gold);
        $this->assertEmpty($found);
    }

    public function testBattleResultsMonsterIsDeadAndCharacterGainedQuestItemMonsterDropChanceIsMax() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            GoldRushCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
        ]);

        $character   = $this->character->updateSkill('Looting', ['level' => 1])->getCharacter();
        $user        = $this->character->getUser();
        $monster     = $this->monster->getMonster();

        $itemId = $this->createItem([
            'name' => 'quest item',
            'type' => 'quest',
        ])->id;

        $monster->update([
            'quest_item_id' => $itemId,
            'quest_item_drop_chance' => 1.00,
        ]);

        $monster = $monster->refresh();

        $currentGold = $character->gold;

        $response = $this->actingAs($user)
            ->json('POST', '/api/battle-results/' . $character->id, [
                'is_defender_dead' => true,
                'defender_type' => 'monster',
                'monster_id' => $monster->id,
            ])
            ->response;

        $character = $character->refresh();

        $found = $character->inventory->slots->filter(function($slot) use ($itemId) {
            return $slot->item_id === $itemId;
        })->all();

        $this->assertEquals(200, $response->status());
        $this->assertTrue($currentGold !== $character->gold);
        $this->assertTrue(count($character->inventory->slots) > 1);
        $this->assertNotEmpty($found);
    }

    public function testBattleResultsCharacterCannotPickUpItem() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            GoldRushCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
        ]);

        $character   = $this->character->updateSkill('Looting', ['level' => 100])
                                       ->updateCharacter(['inventory_max' => 0])
                                       ->getCharacter();
        $user        = $this->character->getUser();
        $monster     = $this->monster->getMonster();

        $currentGold = $character->gold;

        $response = $this->actingAs($user)
                         ->json('POST', '/api/battle-results/' . $character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $monster->id,
                         ])
                         ->response;

        $character = $this->character->getCharacter();

        $this->assertEquals(200, $response->status());
        $this->assertTrue($currentGold !== $character->gold);
        $this->assertTrue(count($character->inventory->slots) === 1);
    }

    public function testBattleResultsMonsterIsDeadAndCharacterGainedGoldRush() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            AttackTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
        ]);

        $character   = $this->character->updateSkill('Looting', ['level' => 100])
                                       ->getCharacter();
        $user        = $this->character->getUser();
        $monster     = $this->monster->getMonster();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/battle-results/' . $character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $monster->id,
                         ])
                         ->response;

        $character = $this->character->getCharacter();

        $this->assertEquals(200, $response->status());
        $this->assertNotEquals(0, $character->gold);
    }

    public function testBattleResultsMonsterIsDeadCannotAttackAgain() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            GoldRushCheckEvent::class,
            ShowTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
        ]);

        $character = $this->character->getCharacter();
        $user      = $this->character->getUser();
        $monster   = $this->monster->getMonster();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/battle-results/' . $character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $monster->id,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $this->assertFalse($this->character->getCharacter()->can_attack);
    }

    public function testCharacterGetsFullXPWhenMonsterMaxLevelIsHigherThenCharacterLevel() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            GoldRushCheckEvent::class,
            ShowTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
        ]);

        $character = $this->character->getCharacter();
        $monster   = $this->monster->updateMonster(['max_level' => 5])->getMonster();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/battle-results/' . $character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $monster->id,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $character = $this->character->getCharacter();

        $this->assertEquals(10, $character->xp);
    }

    public function testCharacterGetsOneThirdXPWhenMonsterMaxLevelIsLowerThenCharacterLevel() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            GoldRushCheckEvent::class,
            ShowTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
        ]);

        $character = $this->character->updateCharacter(['level' => 500])->getCharacter();
        $monster   = $this->monster->updateMonster(['max_level' => 5])->getMonster();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/battle-results/' . $character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $monster->id,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $this->assertEquals(3.00, $this->character->getCharacter()->xp);
    }

    public function testCharacterSeesErrorForUnknownType() {
        Queue::Fake();

        Event::fake([
            ServerMessageEvent::class,
            DropsCheckEvent::class,
            GoldRushCheckEvent::class,
            ShowTimeOutEvent::class,
            UpdateTopBarBroadcastEvent::class,
        ]);

        $character = $this->character->updateCharacter(['level' => 500])->getCharacter();
        $monster   = $this->monster->updateMonster(['max_level' => 5])->getMonster();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/battle-results/' . $character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'apple-sauce',
                             'monster_id' => $monster->id,
                         ])
                         ->response;

        $this->assertEquals(422, $response->status());
        $this->assertEquals('Could not find type of defender.', json_decode($response->content())->message);
    }

    public function testWhenNotLoggedInCannotAccessBattleResults() {

        $response = $this->json('POST', '/api/battle-results/1')
                         ->response;

        $this->assertEquals(401, $response->status());
    }

    public function testWhenCharacterIsDeadReturnFourOhOne() {
        Queue::fake();
        Event::fake([CharacterIsDeadBroadcastEvent::class, UpdateTopBarEvent::class]);

        $character = $this->character->updateCharacter(['is_dead' => true])->getCharacter();

        $response = $this->json('POST', '/api/battle-revive/' . $character->id)
                         ->response;

        $this->assertEquals(401, $response->status());
    }

    public function testCharacterCannotFightWhenDead() {
        Queue::fake();
        Event::fake([CharacterIsDeadBroadcastEvent::class, UpdateTopBarEvent::class]);

        $character = $this->character->updateCharacter(['is_dead' => true])->getCharacter();
        $monster   = $this->monster->updateMonster(['max_level' => 5])->getMonster();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/battle-results/' . $character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'apple-sauce',
                             'monster_id' => $monster->id,
                         ])
                         ->response;

        $this->assertEquals("You are dead and must revive before trying to do that. Dead people can't do things.", json_decode($response->content())->error);
        $this->assertEquals(422, $response->status());
    }

    public function testWhenCharacterIsDead() {
        Queue::fake();
        Event::fake([CharacterIsDeadBroadcastEvent::class, UpdateTopBarEvent::class]);

        $character = $this->character->updateCharacter(['is_dead' => true])->getCharacter();
        $user      = $this->character->getUser();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/battle-revive/' . $character->id)
                         ->response;

        $this->assertEquals(200, $response->status());

        $this->assertFalse($this->character->getCharacter()->is_dead);
    }

    public function testSkillLevelUpFromFight() {
        Queue::fake();

        $character = $this->character->updateSkill('Looting', [
            'xp'                 => 99,
            'xp_max'             => 100,
            'currently_training' => true
        ])->inventoryManagement()
        ->giveItem($this->createItem([
            'name' => 'Sample',
            'skill_name' => 'Looting',
            'skill_training_bonus' => 1.0,
            'type' => 'quest'
        ]))->giveItem($this->createItem([
            'name' => 'Sample',
            'skill_name' => 'Looting',
            'skill_training_bonus' => 1.0,
            'type' => 'quest'
        ]))->getCharacterFactory()->getCharacter();

        $user    = $this->character->getUser();
        $monster = $this->monster->getMonster();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/battle-results/' . $character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $monster->id,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $skill = $this->character->getCharacter()->skills->filter(function($skill) {
            return $skill->name === 'Looting';
        })->first();

        $this->assertEquals(2, $skill->level);
    }

    public function testSkillDoesNotLevelUpFromFight() {
        Queue::fake();

        $character = $this->character->updateSkill('Looting', [
            'level'              => 100,
            'xp'                 => 99,
            'xp_max'             => 100,
            'currently_training' => true
        ])->inventoryManagement()
        ->giveItem($this->createItem([
            'name' => 'Sample',
            'skill_name' => 'Looting',
            'skill_training_bonus' => 1.0,
            'type' => 'quest'
        ]))->giveItem($this->createItem([
            'name' => 'Sample',
            'skill_name' => 'Looting',
            'skill_training_bonus' => 1.0,
            'type' => 'quest'
        ]))->getCharacterFactory()->getCharacter();

        $user    = $this->character->getUser();
        $monster = $this->monster->getMonster();

        $response = $this->actingAs($user)
                         ->json('POST', '/api/battle-results/' . $character->id, [
                             'is_defender_dead' => true,
                             'defender_type' => 'monster',
                             'monster_id' => $monster->id,
                         ])
                         ->response;

        $this->assertEquals(200, $response->status());

        $skill = $this->character->getCharacter()->skills->filter(function($skill) {
            return $skill->name === 'Looting';
        })->first();

        // Skill Did Not Level Up:
        $this->assertEquals(100, $skill->level);
    }
}
