<?php

namespace Tests\Unit\Game\Character\Builders\AttackBuilders;

use App\Flare\Values\AttackTypeValue;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Character\Builders\AttackBuilders\CharacterPvpCacheData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Character;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class CharacterPvpCacheDataTest extends TestCase {

    use RefreshDatabase, CreateItem;

    private ?CharacterFactory $character;

    private ?CharacterFactory $defender;

    private ?CharacterPvpCacheData $characterPvpCacheData;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();

        $this->defender = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation();

        $this->characterPvpCacheData = resolve(CharacterPvpCacheData::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
        $this->defender = null;
        $this->characterPvpCacheData = null;
    }

    public function testSetAndFetchHealth() {

        $character = $this->character->getCharacter();
        $defender = $this->defender->getCharacter();

        $this->characterPvpCacheData->setPvpData($character, $defender, 100, 10);

        $attackerHealth = Cache::get('pvp-cache-' . $character->id);
        $defenderHealth = Cache::get('pvp-cache-' . $defender->id);

        $this->assertEquals(100, $attackerHealth);
        $this->assertEquals(10, $defenderHealth);
    }

    public function testDeleteDefenderCacheData() {

        $character = $this->character->getCharacter();
        $defender = $this->defender->getCharacter();

        $this->characterPvpCacheData->setPvpData($character, $defender, 100, 10);

        $this->characterPvpCacheData->removeFromPvpCache($defender);

        $defenderHealth = Cache::get('pvp-cache-' . $defender->id);

        $this->assertNull($defenderHealth);
    }

    public function testGetNullForCacheObjectWhenDefenderCacheDoesNotExist() {

        $character = $this->character->getCharacter();
        $defender = $this->defender->getCharacter();

        $this->characterPvpCacheData->setPvpData($character, $defender, 100, 10);

        $this->characterPvpCacheData->removeFromPvpCache($defender);

        $data = $this->characterPvpCacheData->fetchPvpCacheObject($character, $defender);

        $this->assertNull($data);
    }

    public function testGetHealthObjectForPvpCacheData() {

        $character = $this->character->getCharacter();
        $defender = $this->defender->getCharacter();

        $this->characterPvpCacheData->setPvpData($character, $defender, 100, 10);

        $data = $this->characterPvpCacheData->fetchPvpCacheObject($character, $defender);

        $this->assertEquals([
            'attacker_health' => 100,
            'defender_health' => 10,
        ], $data);
    }

    public function testUpdatePlayerHealth() {

        $character = $this->character->getCharacter();
        $defender = $this->defender->getCharacter();

        $this->characterPvpCacheData->setPvpData($character, $defender, 100, 10);

        $this->characterPvpCacheData->updatePlayerHealth($character, 200);

        $data = $this->characterPvpCacheData->fetchPvpCacheObject($character, $defender);

        $this->assertEquals([
            'attacker_health' => 200,
            'defender_health' => 10,
        ], $data);
    }

    public function testCacheObjectDoesExistForBothDefenderAndAttacker() {

        $character = $this->character->getCharacter();
        $defender = $this->defender->getCharacter();

        $this->characterPvpCacheData->setPvpData($character, $defender, 100, 10);

        $exists = $this->characterPvpCacheData->pvpCacheExists($character, $defender);

        $this->assertTrue($exists);
    }

}
