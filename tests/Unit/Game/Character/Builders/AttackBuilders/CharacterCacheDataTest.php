<?php

namespace Tests\Unit\Game\Character\Builders\AttackBuilders;

use App\Flare\Models\Character;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class CharacterCacheDataTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CharacterCacheData $characterCacheData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();

        $this->characterCacheData = resolve(CharacterCacheData::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->characterCacheData = null;
    }

    private function setUpCharacterForTests(): Character
    {
        $item = $this->createItem([
            'type' => WeaponTypes::STAVE,
            'base_damage' => 10,
        ]);

        $spellDamage = $this->createItem([
            'type' => SpellTypes::DAMAGE,
            'base_damage' => 10,
        ]);

        return $this->character->inventoryManagement()
            ->giveItem($item, true, 'left-hand')
            ->giveItem($spellDamage, true, 'spell-one')
            ->getCharacter();
    }

    public function test_cached_ac_is_set_up()
    {

        $character = $this->setUpCharacterForTests();

        $this->characterCacheData->setCharacterDefendAc($character, 10);

        $this->assertEquals(10, $this->characterCacheData->getCharacterDefenceAc($character));
    }

    public function test_get_attack_data_for_attack_type()
    {
        $character = $this->setUpCharacterForTests();

        $data = $this->characterCacheData->getDataFromAttackCache($character, AttackTypeValue::ATTACK);

        $this->assertGreaterThan(0, $data['weapon_damage']);
    }

    public function test_get_stat_from_character_sheet_cache_when_cache_does_not_exist()
    {
        $character = $this->setUpCharacterForTests();

        $value = $this->characterCacheData->getCachedCharacterData($character, 'str');

        $this->assertGreaterThan(0, $value);
    }

    public function test_get_stat_from_character_sheet_cache_data_when_level_does_not_match()
    {
        $character = $this->setUpCharacterForTests();

        Cache::put('character-sheet-'.$character->id, [
            'level' => 0,
        ]);

        $value = $this->characterCacheData->getCachedCharacterData($character, 'str');

        $this->assertGreaterThan(0, $value);
    }

    public function test_delete_character_sheet_data()
    {
        $character = $this->setUpCharacterForTests();

        Cache::put('character-sheet-'.$character->id, [
            'level' => 0,
        ]);

        $this->characterCacheData->deleteCharacterSheet($character);

        $this->assertNull(Cache::get('character-sheet-'.$character->id));
    }

    public function test_get_existing_character_sheet_cache()
    {
        $character = $this->setUpCharacterForTests();

        $characterSheet = [
            'level' => 0,
        ];

        Cache::put('character-sheet-'.$character->id, $characterSheet);

        $data = $this->characterCacheData->getCharacterSheetCache($character);

        $this->assertEquals($characterSheet, $data);
    }

    public function test_get_character_sheet_when_it_does_not_exist()
    {
        $character = $this->setUpCharacterForTests();

        $data = $this->characterCacheData->getCharacterSheetCache($character);

        $this->assertNotEmpty($data);
    }

    public function test_update_existing_character_sheet()
    {
        $character = $this->setUpCharacterForTests();

        $characterSheet = [
            'level' => 0,
        ];

        Cache::put('character-sheet-'.$character->id, $characterSheet);

        $this->characterCacheData->updateCharacterSheetCache($character, [
            'name' => 'Hello',
        ]);

        $data = Cache::get('character-sheet-'.$character->id);

        $this->assertEquals('Hello', $data['name']);
    }

    public function test_update_non_existent_character_sheet()
    {
        $character = $this->setUpCharacterForTests();

        $this->characterCacheData->updateCharacterSheetCache($character, [
            'name' => 'Hello',
        ]);

        $data = Cache::get('character-sheet-'.$character->id);

        $this->assertEquals('Hello', $data['name']);
    }

    public function test_get_character_sheet_cache()
    {
        $character = $this->setUpCharacterForTests();

        $data = $this->characterCacheData->characterSheetCache($character);

        $this->assertNotEmpty($data);
    }
}
