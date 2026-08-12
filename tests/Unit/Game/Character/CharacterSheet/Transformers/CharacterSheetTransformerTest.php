<?php

namespace Tests\Unit\Game\Character\CharacterSheet\Transformers;

use App\Flare\Models\Character;
use App\Game\Character\CharacterSheet\Transformers\CharacterSheetTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CharacterSheetTransformerTest extends TestCase
{
    use RefreshDatabase;

    private ?CharacterSheetTransformer $transformer;

    private ?Character $character;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = resolve(CharacterSheetTransformer::class);
        $this->character = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->transformer = null;
        $this->character = null;
    }

    public function test_transform_returns_complete_character_sheet_contract(): void
    {
        $character = $this->character;

        $data = $this->transformer->transform($character);

        $expectedKeys = [
            'id', 'user_id', 'name', 'class', 'class_id', 'race', 'race_id', 'game_map_id', 'map_name',
            'to_hit_stat', 'damage_stat', 'level', 'max_level', 'xp', 'xp_next', 'class_bonus_chance',
            'str_raw', 'dur_raw', 'dex_raw', 'chr_raw', 'int_raw', 'agi_raw', 'focus_raw',
            'str_modded', 'dur_modded', 'dex_modded', 'chr_modded', 'int_modded', 'agi_modded', 'focus_modded',
            'attack', 'ac', 'health', 'resurrection_chance', 'weapon_attack', 'voided_weapon_attack',
            'ring_damage', 'spell_damage', 'voided_spell_damage', 'healing_amount', 'voided_healing_amount',
            'gold', 'gold_dust', 'shards', 'copper_coins', 'gold_bars',
            'inventory_count', 'resistance_info', 'elemental_atonements', 'reincarnation_info',
        ];

        $this->assertSame([], array_diff($expectedKeys, array_keys($data)));
    }

    public function test_transform_returns_numeric_gameplay_values_not_formatted_strings(): void
    {
        $character = $this->character;

        $data = $this->transformer->transform($character);

        $this->assertIsInt($data['level']);
        $this->assertIsInt($data['gold']);
        $this->assertIsInt($data['gold_dust']);
        $this->assertIsInt($data['shards']);
        $this->assertIsInt($data['copper_coins']);
        $this->assertIsNumeric($data['str_raw']);
        $this->assertIsNumeric($data['attack']);
    }

    public function test_transform_preserves_runtime_and_access_state_from_base_info_transformer(): void
    {
        $character = $this->character;

        $data = $this->transformer->transform($character);

        $this->assertArrayHasKey('can_craft', $data);
        $this->assertArrayHasKey('can_attack', $data);
        $this->assertArrayHasKey('is_automation_running', $data);
        $this->assertArrayHasKey('is_batch_crafting_running', $data);
        $this->assertArrayHasKey('is_silenced', $data);
        $this->assertArrayHasKey('is_in_timeout', $data);
        $this->assertArrayHasKey('can_use_work_bench', $data);
        $this->assertArrayHasKey('current_fame_tasks', $data);
        $this->assertArrayHasKey('faction_loyalty_warning_notices', $data);
    }

    public function test_transform_returns_elemental_atonements_with_consistent_shape_when_no_items_equipped(): void
    {
        $character = $this->character;

        $data = $this->transformer->transform($character);

        $this->assertSame([
            'atonements' => [],
            'highest_element' => [
                'name' => 'N/A',
                'damage' => 0,
            ],
        ], $data['elemental_atonements']);
    }
}
