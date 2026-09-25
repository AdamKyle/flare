<?php

namespace Tests\Unit\Game\Character\Builders\AttackBuilders;

use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Combat\Values\AttackType;
use App\Game\Core\Items\Values\ItemType;
use ErrorException;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\Setup\Battle\ServerFight\BuildMonsterFactory;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateItemAffix;

class CharacterCacheDataTest extends TestCase
{
    use CreateItem, CreateItemAffix, RefreshDatabase;

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

        $data = $this->characterCacheData->getDataFromAttackCache($character, AttackType::ATTACK->value);

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

    public function test_get_cached_character_data_discards_an_unreadable_character_sheet_and_rebuilds_it_from_the_character(): void
    {
        Log::spy();

        $character = $this->character->equipBasicAttackLoadout()->getCharacter();
        $cacheKey = 'character-sheet-'.$character->id;

        Cache::swap(new Repository(new class($cacheKey) extends ArrayStore
        {
            private bool $hasFailedRead = false;

            public function __construct(private readonly string $unreadableKey)
            {
                parent::__construct();
            }

            public function get($key)
            {
                if ($key !== $this->unreadableKey || $this->hasFailedRead) {
                    return parent::get($key);
                }

                $this->hasFailedRead = true;

                throw new ErrorException('unserialize(): Error at offset 0 of 42 bytes');
            }
        }));

        Cache::put($cacheKey, ['level' => number_format($character->level), 'str' => -1]);

        $value = $this->characterCacheData->getCachedCharacterData($character, 'str');

        $this->assertGreaterThan(0, $value);
        $this->assertSame($value, Cache::get($cacheKey)['str']);
        Log::shouldHaveReceived('warning')->once()->with(
            'Discarded an unreadable Character sheet cache entry.',
            Mockery::on(fn (array $context): bool => $context['character_id'] === $character->id
                && $context['cache_key'] === $cacheKey
                && $context['exception_class'] === ErrorException::class
                && $context['exception_message'] === 'unserialize(): Error at offset 0 of 42 bytes'),
        );
    }

    public function test_get_cached_character_data_returns_a_value_from_a_valid_character_sheet_for_the_current_level(): void
    {
        $character = $this->character->equipBasicAttackLoadout()->getCharacter();

        Cache::put('character-sheet-'.$character->id, [
            'level' => number_format($character->level),
            'str' => 12345,
        ]);

        $this->assertSame(12345, $this->characterCacheData->getCachedCharacterData($character, 'str'));
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

    public function test_character_sheet_cache_stat_affixes_all_stat_reduction_is_plain_array_data_that_build_monster_can_consume_after_serialization()
    {
        $prefix = $this->createItemAffix([
            'type' => 'prefix',
            'reduces_enemy_stats' => true,
            'str_reduction' => 0.5,
        ]);
        $item = $this->createItem(['type' => 'sword', 'item_prefix_id' => $prefix->id]);

        $character = $this->character->inventoryManagement()
            ->giveItem($item, true, 'left-hand')
            ->getCharacter();

        $data = $this->characterCacheData->characterSheetCache($character);

        $rehydratedStatAffixes = unserialize(serialize($data['stat_affixes']));

        $this->assertIsArray($rehydratedStatAffixes['all_stat_reduction']);
        $this->assertSame(0.5, $rehydratedStatAffixes['all_stat_reduction']['str_reduction']);

        $randomNumberGenerator = Mockery::mock(RandomNumberGenerator::class);
        $randomNumberGenerator->shouldReceive('numberBetween')->once()->with('100', '100')->andReturn(100);

        $buildMonster = (new BuildMonsterFactory)->buildBuildMonster(randomNumberGenerator: $randomNumberGenerator);

        $result = $buildMonster->buildMonster([
            'name' => 'Test Monster',
            'only_for_location_type' => null,
            'health_range' => '100-100',
            'increases_damage_by' => null,
            'accuracy' => 0.5,
            'casting_accuracy' => 0.5,
            'dodge' => 0.5,
            'criticality' => 0.5,
            'spell_evasion' => 0.5,
            'affix_resistance' => 2.0,
            'counter_resistance_chance' => 0.5,
            'ambush_resistance_chance' => 0.5,
            'str' => 100,
            'int' => 100,
            'dex' => 100,
            'dur' => 100,
            'agi' => 100,
            'chr' => 100,
            'focus' => 100,
        ], $rehydratedStatAffixes, 0.0, 0.0);

        $this->assertSame(50.0, $result->getMonsterStat('str'));
    }
}
