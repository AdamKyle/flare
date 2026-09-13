<?php

namespace Tests\Unit\Admin\MapGems\Imports\Sheets;

use App\Admin\MapGems\Imports\Sheets\MapGemsSheet;
use App\Flare\Models\GameMapGemParamter;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\CreateGameLocationGemParamter;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGameSkill;

class MapGemsSheetTest extends TestCase
{
    use CreateGameLocationGemParamter, CreateGameMap, CreateGameMapGemParamter, CreateGameSkill, RefreshDatabase;

    public function test_valid_workbook_row_creates_a_new_profile(): void
    {
        $map = $this->createGameMap(['name' => 'Import Map']);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'gem_world_name']),
            collect(['Imported Profile', $map->name, 'The Imported Requiem']),
        ]));

        $profile = GameMapGemParamter::where('name', 'Imported Profile')->first();

        $this->assertNotNull($profile);
        $this->assertSame($map->id, $profile->game_map_id);
        $this->assertSame('The Imported Requiem', $profile->gem_world_name);
    }

    public function test_valid_workbook_row_updates_existing_profile_by_name(): void
    {
        $map = $this->createGameMap(['name' => 'Update Map']);
        $profile = $this->createGameMapGemParamter([
            'name' => 'Existing Profile',
            'game_map_id' => $map->id,
            'gold_gain_range' => '0.01-0.05',
        ]);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'gem_world_name', 'gold_gain_range']),
            collect(['Existing Profile', $map->name, 'The Updated Requiem', '0.10-0.20']),
        ]));

        $profile->refresh();

        $this->assertSame('0.10-0.20', $profile->gold_gain_range);
        $this->assertSame('The Updated Requiem', $profile->gem_world_name);
    }

    public function test_unknown_game_map_name_fails_the_import(): void
    {
        $this->expectException(RuntimeException::class);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name']),
            collect(['Bad Map Profile', 'Does Not Exist Map']),
        ]));
    }

    public function test_unknown_crafting_skill_name_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Skill Map']);
        $this->expectException(RuntimeException::class);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'crafting_skill_names']),
            collect(['Bad Skill Profile', $map->name, 'Does Not Exist Skill']),
        ]));
    }

    public function test_crafting_skill_names_resolve_to_ids(): void
    {
        $map = $this->createGameMap(['name' => 'Crafting Map']);
        $skill = $this->createGameSkill(['name' => 'Import Skill', 'can_train' => false]);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'gem_world_name', 'crafting_skill_names']),
            collect(['Crafting Profile', $map->name, 'The Crafting Requiem', $skill->name]),
        ]));

        $profile = GameMapGemParamter::where('name', 'Crafting Profile')->first();

        $this->assertSame([$skill->id], $profile->crafting_skill_ids);
    }

    public function test_trainable_crafting_skill_name_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Trainable Skill Map']);
        $skill = $this->createGameSkill(['name' => 'Trainable Skill', 'can_train' => true]);
        $this->expectException(RuntimeException::class);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'crafting_skill_names']),
            collect(['Trainable Skill Profile', $map->name, $skill->name]),
        ]));
    }

    public function test_generated_source_game_map_fails_the_import(): void
    {
        $this->createGameMap(['name' => 'Generated Source Map', 'generated_map_type' => 'gem-world']);
        $this->expectException(RuntimeException::class);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name']),
            collect(['Generated Source Profile', 'Generated Source Map']),
        ]));
    }

    public function test_malformed_range_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Malformed Range Map']);
        $this->expectException(RuntimeException::class);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'gold_gain_range']),
            collect(['Malformed Range Profile', $map->name, 'not-a-range']),
        ]));
    }

    public function test_negative_range_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Negative Range Map']);
        $this->expectException(RuntimeException::class);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'gold_gain_range']),
            collect(['Negative Range Profile', $map->name, '-5-10']),
        ]));
    }

    public function test_malformed_legacy_combined_rarity_range_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Malformed Legacy Range Map']);
        $this->expectException(RuntimeException::class);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'unique_mythic_cosmic_item_drop_chance_increase_range']),
            collect(['Malformed Legacy Range Profile', $map->name, '1--5']),
        ]));
    }

    public function test_atonement_label_resolves_to_gem_type_value(): void
    {
        $map = $this->createGameMap(['name' => 'Atonement Map']);
        $label = GemTypeValue::getNames()[GemTypeValue::FIRE];

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'gem_world_name', 'monster_atonement']),
            collect(['Atonement Profile', $map->name, 'The Atonement Requiem', $label]),
        ]));

        $profile = GameMapGemParamter::where('name', 'Atonement Profile')->first();

        $this->assertSame(GemTypeValue::FIRE, $profile->monster_atonement);
    }

    public function test_fallback_combined_rarity_range_applies_to_unique_mythic_and_cosmic(): void
    {
        $map = $this->createGameMap(['name' => 'Fallback Map']);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'gem_world_name', 'unique_mythic_cosmic_item_drop_chance_increase_range']),
            collect(['Fallback Profile', $map->name, 'The Fallback Requiem', '0.01-0.02']),
        ]));

        $profile = GameMapGemParamter::where('name', 'Fallback Profile')->first();

        $this->assertSame('0.01-0.02', $profile->unique_item_drop_chance_increase_range);
        $this->assertSame('0.01-0.02', $profile->mythic_item_drop_chance_increase_range);
        $this->assertSame('0.01-0.02', $profile->cosmic_item_drop_chance_increase_range);
    }

    public function test_blank_row_stops_processing_without_touching_rows_after_it(): void
    {
        $map = $this->createGameMap(['name' => 'Blank Stop Map']);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'gem_world_name']),
            collect(['Valid Before Blank Profile', $map->name, 'The Valid Before Blank Requiem']),
            collect([null, null, null]),
            collect(['Should Not Be Reached Profile', $map->name, 'The Unreached Requiem']),
        ]));

        $this->assertNotNull(GameMapGemParamter::where('name', 'Valid Before Blank Profile')->first());
        $this->assertNull(GameMapGemParamter::where('name', 'Should Not Be Reached Profile')->first());
    }

    public function test_zero_only_optional_range_cells_are_normalized_to_null(): void
    {
        $map = $this->createGameMap(['name' => 'Zero Range Map']);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'gem_world_name', 'gold_gain_range', 'crafting_skill_bonus_range', 'item_drop_chance_increase_range', 'unique_item_drop_chance_increase_range']),
            collect(['Zero Range Profile', $map->name, 'The Zero Range Requiem', 0, 0.0, '0', '0-0']),
        ]));

        $profile = GameMapGemParamter::where('name', 'Zero Range Profile')->first();

        $this->assertNull($profile->gold_gain_range);
        $this->assertNull($profile->crafting_skill_bonus_range);
        $this->assertNull($profile->item_drop_chance_increase_range);
        $this->assertNull($profile->unique_item_drop_chance_increase_range);
    }

    public function test_nonzero_scalar_range_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Nonzero Scalar Map']);
        $this->expectException(RuntimeException::class);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'gold_gain_range']),
            collect(['Nonzero Scalar Profile', $map->name, '5']),
        ]));
    }

    public function test_missing_gem_world_name_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Missing World Name Map']);
        $this->expectException(RuntimeException::class);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name']),
            collect(['Missing World Name Profile', $map->name]),
        ]));
    }

    public function test_gem_world_name_containing_technical_phrase_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Technical Phrase Map']);
        $this->expectException(RuntimeException::class);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'gem_world_name']),
            collect(['Technical Phrase Profile', $map->name, 'Technical Phrase Gem Profile']),
        ]));
    }

    public function test_duplicate_gem_world_name_within_workbook_fails_the_import(): void
    {
        $mapOne = $this->createGameMap(['name' => 'Duplicate Name Map One']);
        $mapTwo = $this->createGameMap(['name' => 'Duplicate Name Map Two']);
        $this->expectException(RuntimeException::class);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'gem_world_name']),
            collect(['First Duplicate Profile', $mapOne->name, 'The Duplicated Requiem']),
            collect(['Second Duplicate Profile', $mapTwo->name, 'the duplicated requiem']),
        ]));
    }

    public function test_gem_world_name_matching_a_location_gem_world_name_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Cross Type Map']);
        $this->createGameLocationGemParamter(['gem_world_name' => 'The Shared Requiem']);
        $this->expectException(RuntimeException::class);

        (new MapGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'gem_world_name']),
            collect(['Cross Type Profile', $map->name, 'The Shared Requiem']),
        ]));
    }
}
