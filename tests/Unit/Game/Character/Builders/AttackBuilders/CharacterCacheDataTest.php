<?php

namespace Tests\Unit\Game\Character\Builders\AttackBuilders;

use App\Flare\Items\Values\ItemType;
use App\Flare\Values\AttackTypeValue;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CharacterCacheDataTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_cached_ac_is_set_up()
    {

        $character = $this->character->equipBasicAttackLoadout()->getCharacter();

        $this->characterCacheData->setCharacterDefendAc($character, 10);

        $this->assertEquals(10, $this->characterCacheData->getCharacterDefenceAc($character));
    }

    public function test_get_attack_data_for_attack_type()
    {
        $character = $this->character->equipBasicAttackLoadout()->getCharacter();

        $data = $this->characterCacheData->getDataFromAttackCache($character, AttackTypeValue::ATTACK);

        $this->assertGreaterThan(0, $data['weapon_damage']);
    }

    public function test_get_stat_from_character_sheet_cache_when_cache_does_not_exist()
    {
        $character = $this->character->equipBasicAttackLoadout()->getCharacter();

        $value = $this->characterCacheData->getCachedCharacterData($character, 'str');

        $this->assertGreaterThan(0, $value);
    }

    public function test_get_stat_from_character_sheet_cache_data_when_level_does_not_match()
    {
        $character = $this->character->equipBasicAttackLoadout()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => 0,
        ]);

        $value = $this->characterCacheData->getCachedCharacterData($character, 'str');

        $this->assertGreaterThan(0, $value);
    }

    public function test_delete_character_sheet_data()
    {
        $character = $this->character->equipBasicAttackLoadout()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => 0,
        ]);

        $this->characterCacheData->deleteCharacterSheet($character);

        $this->assertNull(Cache::get('character-sheet-'.$character->id));
    }

    public function test_get_existing_character_sheet_cache()
    {
        $character = $this->character->equipBasicAttackLoadout()->getCharacter();

        $characterSheet = [
            'level' => 0,
        ];

        Cache::put('character-sheet-'.$character->id, $characterSheet);

        $data = $this->characterCacheData->getCharacterSheetCache($character);

        $this->assertEquals($characterSheet, $data);
    }

    public function test_get_character_sheet_when_it_does_not_exist()
    {
        $character = $this->character->equipBasicAttackLoadout()->getCharacter();

        $data = $this->characterCacheData->getCharacterSheetCache($character);

        $this->assertNotEmpty($data);
    }

    public function test_update_existing_character_sheet()
    {
        $character = $this->character->equipBasicAttackLoadout()->getCharacter();

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
        $character = $this->character->equipBasicAttackLoadout()->getCharacter();

        $this->characterCacheData->updateCharacterSheetCache($character, [
            'name' => 'Hello',
        ]);

        $data = Cache::get('character-sheet-'.$character->id);

        $this->assertEquals('Hello', $data['name']);
    }

    public function test_get_character_sheet_cache()
    {
        $character = $this->character->equipBasicAttackLoadout()->getCharacter();

        $data = $this->characterCacheData->characterSheetCache($character);

        $this->assertNotEmpty($data);
    }

    public function test_character_sheet_cache_weapon_attack_includes_claw_damage()
    {
        $item = $this->createItem([
            'type' => ItemType::CLAW->value,
            'base_damage' => 100,
        ]);

        $character = $this->character->inventoryManagement()
            ->giveItem($item, true, 'left-hand')
            ->getCharacter();

        $data = $this->characterCacheData->characterSheetCache($character);

        $this->assertEquals(100, $data['weapon_attack']);
    }

    public function test_character_sheet_cache_weapon_attack_excludes_ring_spell_damage_and_spell_healing()
    {
        $character = $this->character->inventoryManagement()
            ->giveItem($this->createItem([
                'type' => ItemType::GUN->value,
                'base_damage' => 100,
            ]), true, 'left-hand')
            ->giveItem($this->createItem([
                'type' => ItemType::RING->value,
                'base_damage' => 1000,
            ]), true, 'ring-one')
            ->giveItem($this->createItem([
                'type' => ItemType::SPELL_DAMAGE->value,
                'base_damage' => 1000,
            ]), true, 'spell-one')
            ->giveItem($this->createItem([
                'type' => ItemType::SPELL_HEALING->value,
                'base_damage' => 1000,
                'base_healing' => 1000,
            ]), true, 'spell-two')
            ->getCharacter();

        $data = $this->characterCacheData->characterSheetCache($character);

        $this->assertLessThan(1000, $data['weapon_attack']);
    }
}
