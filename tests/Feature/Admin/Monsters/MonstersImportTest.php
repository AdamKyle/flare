<?php

namespace Tests\Feature\Admin\Monsters;

use App\Admin\Monsters\Imports\Sheets\MonstersSheet;
use App\Flare\Models\Monster;
use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateMonsterImportRow;

class MonstersImportTest extends TestCase
{
    use CreateGameMap, CreateItem, CreateMonsterImportRow, RefreshDatabase;

    private const HEADERS = [
        'id', 'name', 'damage_stat', 'game_map_id', 'max_level', 'xp', 'gold', 'health_range',
        'attack_range', 'drop_check', 'only_for_location_type', 'str', 'dur', 'dex', 'chr', 'int',
        'agi', 'focus', 'ac', 'accuracy', 'dodge', 'criticality', 'ambush_chance', 'ambush_resistance',
        'counter_chance', 'counter_resistance', 'can_cast', 'max_spell_damage', 'casting_accuracy',
        'spell_evasion', 'max_affix_damage', 'affix_resistance', 'healing_percentage',
        'entrancing_chance', 'devouring_light_chance', 'devouring_darkness_chance',
        'life_stealing_resistance', 'quest_item_id', 'quest_item_drop_chance', 'is_celestial_entity',
        'celestial_type', 'gold_cost', 'gold_dust_cost', 'shards', 'is_raid_monster', 'is_raid_boss',
        'raid_special_attack_type', 'fire_atonement', 'ice_atonement', 'water_atonement',
    ];

    public function test_valid_workbook_writes_every_managed_field_group(): void
    {
        $map = $this->createGameMap(['name' => 'Import Map']);
        $questItem = $this->createItem(['name' => 'Import Quest Item', 'type' => 'quest']);

        $row = $this->minimalMonsterImportRow([
            'quest_item_id' => 'Import Quest Item',
            'quest_item_drop_chance' => 0.5,
            'accuracy' => 0.2,
            'can_cast' => 'true',
            'max_spell_damage' => 50,
            'is_celestial_entity' => 'true',
            'celestial_type' => 1,
        ]);

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => $row[$header] ?? null, self::HEADERS)),
        ]);

        $sheet = new MonstersSheet;
        $sheet->collection($rows);

        $this->assertTrue($sheet->wasSuccessful());
        $monster = Monster::where('name', 'Import Monster')->first();
        $this->assertNotNull($monster);
        $this->assertSame($map->id, $monster->game_map_id);
        $this->assertSame($questItem->id, $monster->quest_item_id);
        $this->assertEquals(0.5, $monster->quest_item_drop_chance);
        $this->assertTrue((bool) $monster->can_cast);
        $this->assertTrue((bool) $monster->is_celestial_entity);
    }

    public function test_missing_required_field_is_rejected(): void
    {
        $this->createGameMap(['name' => 'Import Map']);

        $row = $this->minimalMonsterImportRow(['max_level' => null]);

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => $row[$header] ?? null, self::HEADERS)),
        ]);

        $sheet = new MonstersSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Monster::count());
    }

    public function test_invalid_damage_stat_is_rejected(): void
    {
        $this->createGameMap(['name' => 'Import Map']);

        $row = $this->minimalMonsterImportRow(['damage_stat' => 'not_a_stat']);

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => $row[$header] ?? null, self::HEADERS)),
        ]);

        $sheet = new MonstersSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Monster::count());
    }

    public function test_invalid_map_is_rejected(): void
    {
        $row = $this->minimalMonsterImportRow(['game_map_id' => 'Does Not Exist']);

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => $row[$header] ?? null, self::HEADERS)),
        ]);

        $sheet = new MonstersSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Monster::count());
    }

    public function test_invalid_quest_item_is_rejected(): void
    {
        $this->createGameMap(['name' => 'Import Map']);

        $row = $this->minimalMonsterImportRow(['quest_item_id' => 'Does Not Exist']);

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => $row[$header] ?? null, self::HEADERS)),
        ]);

        $sheet = new MonstersSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Monster::count());
    }

    public function test_invalid_location_type_is_rejected(): void
    {
        $this->createGameMap(['name' => 'Import Map']);

        $row = $this->minimalMonsterImportRow(['only_for_location_type' => 999]);

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => $row[$header] ?? null, self::HEADERS)),
        ]);

        $sheet = new MonstersSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Monster::count());
    }

    public function test_alchemy_church_location_type_is_accepted(): void
    {
        $this->createGameMap(['name' => 'Import Map']);

        $row = $this->minimalMonsterImportRow(['only_for_location_type' => LocationType::ALCHEMY_CHURCH->value]);

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => $row[$header] ?? null, self::HEADERS)),
        ]);

        $sheet = new MonstersSheet;
        $sheet->collection($rows);

        $this->assertTrue($sheet->wasSuccessful());
    }

    public function test_cave_of_memories_location_type_is_accepted(): void
    {
        $this->createGameMap(['name' => 'Import Map']);

        $row = $this->minimalMonsterImportRow(['only_for_location_type' => LocationType::CAVE_OF_MEMORIES->value]);

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => $row[$header] ?? null, self::HEADERS)),
        ]);

        $sheet = new MonstersSheet;
        $sheet->collection($rows);

        $this->assertTrue($sheet->wasSuccessful());
    }

    public function test_cave_of_shadows_location_type_is_rejected(): void
    {
        $this->createGameMap(['name' => 'Import Map']);

        $row = $this->minimalMonsterImportRow(['only_for_location_type' => LocationType::CAVE_OF_SHADOWS->value]);

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => $row[$header] ?? null, self::HEADERS)),
        ]);

        $sheet = new MonstersSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Monster::count());
    }

    public function test_invalid_raid_attack_type_is_rejected(): void
    {
        $this->createGameMap(['name' => 'Import Map']);

        $row = $this->minimalMonsterImportRow(['raid_special_attack_type' => 999]);

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => $row[$header] ?? null, self::HEADERS)),
        ]);

        $sheet = new MonstersSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Monster::count());
    }

    public function test_raid_monster_and_boss_mutual_exclusion_is_rejected(): void
    {
        $this->createGameMap(['name' => 'Import Map']);

        $row = $this->minimalMonsterImportRow(['is_raid_monster' => 'true', 'is_raid_boss' => 'true']);

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => $row[$header] ?? null, self::HEADERS)),
        ]);

        $sheet = new MonstersSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Monster::count());
    }

    public function test_arbitrary_non_empty_string_is_not_treated_as_boolean_true(): void
    {
        $this->createGameMap(['name' => 'Import Map']);

        $row = $this->minimalMonsterImportRow(['can_cast' => 'FALSE']);

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => $row[$header] ?? null, self::HEADERS)),
        ]);

        $sheet = new MonstersSheet;
        $sheet->collection($rows);

        $this->assertTrue($sheet->wasSuccessful());
        $monster = Monster::where('name', 'Import Monster')->first();
        $this->assertFalse((bool) $monster->can_cast);
    }

    public function test_validation_failure_produces_zero_writes(): void
    {
        $this->createGameMap(['name' => 'Import Map']);

        $rowOne = $this->minimalMonsterImportRow(['name' => 'Valid Monster']);
        $rowTwo = $this->minimalMonsterImportRow(['name' => 'Invalid Monster', 'game_map_id' => 'Does Not Exist']);

        $rows = collect([
            collect(self::HEADERS),
            collect(array_map(fn ($header) => $rowOne[$header] ?? null, self::HEADERS)),
            collect(array_map(fn ($header) => $rowTwo[$header] ?? null, self::HEADERS)),
        ]);

        $sheet = new MonstersSheet;
        $sheet->collection($rows);

        $this->assertFalse($sheet->wasSuccessful());
        $this->assertSame(0, Monster::count());
    }
}
