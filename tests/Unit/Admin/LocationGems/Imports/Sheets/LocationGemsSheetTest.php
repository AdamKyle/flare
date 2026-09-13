<?php

namespace Tests\Unit\Admin\LocationGems\Imports\Sheets;

use App\Admin\LocationGems\Imports\Sheets\LocationGemsSheet;
use App\Flare\Models\GameLocationGemParamter;
use App\Game\Gems\Values\GemTypeValue;
use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\CreateGameLocationGemParamter;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateLocation;

class LocationGemsSheetTest extends TestCase
{
    use CreateGameLocationGemParamter, CreateGameMap, CreateGameMapGemParamter, CreateGameSkill, CreateLocation, RefreshDatabase;

    public function test_valid_workbook_row_creates_a_new_profile(): void
    {
        $map = $this->createGameMap(['name' => 'Import Map']);
        $location = $this->createLocation(['name' => 'Import Location', 'game_map_id' => $map->id, 'type' => 1]);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name', 'gem_world_name']),
            collect(['Imported Profile', $map->name, $location->name, 'The Imported Hush']),
        ]));

        $profile = GameLocationGemParamter::where('name', 'Imported Profile')->first();

        $this->assertNotNull($profile);
        $this->assertSame($location->id, $profile->location_id);
        $this->assertSame('The Imported Hush', $profile->gem_world_name);
    }

    public function test_valid_workbook_row_updates_existing_profile_by_name(): void
    {
        $profile = $this->createGameLocationGemParamter(['name' => 'Existing Profile', 'gold_gain_range' => '0.01-0.05']);
        $location = $profile->location()->with('map')->first();

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name', 'gem_world_name', 'gold_gain_range']),
            collect(['Existing Profile', $location->map->name, $location->name, 'The Updated Hush', '0.10-0.20']),
        ]));

        $profile->refresh();

        $this->assertSame('0.10-0.20', $profile->gold_gain_range);
        $this->assertSame('The Updated Hush', $profile->gem_world_name);
    }

    public function test_unknown_location_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Known Map']);
        $this->expectException(RuntimeException::class);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name']),
            collect(['Bad Location Profile', $map->name, 'Does Not Exist Location']),
        ]));
    }

    public function test_unknown_game_map_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Real Existing Map']);
        $location = $this->createLocation(['name' => 'Orphaned Location', 'game_map_id' => $map->id, 'type' => 1]);
        $this->expectException(RuntimeException::class);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name']),
            collect(['Bad Map Profile', 'Does Not Exist Map', $location->name]),
        ]));
    }

    public function test_generated_map_location_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Generated Location Map', 'generated_map_type' => 'gem-world']);
        $location = $this->createLocation(['name' => 'Generated Map Location', 'game_map_id' => $map->id, 'type' => 1]);
        $this->expectException(RuntimeException::class);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name']),
            collect(['Generated Map Profile', $map->name, $location->name]),
        ]));
    }

    public function test_ineligible_location_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Ineligible Location Map']);
        $location = $this->createLocation(['name' => 'Ineligible Location', 'game_map_id' => $map->id, 'type' => null]);
        $this->expectException(RuntimeException::class);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name']),
            collect(['Ineligible Location Profile', $map->name, $location->name]),
        ]));
    }

    public function test_trainable_crafting_skill_name_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Trainable Skill Map']);
        $location = $this->createLocation(['name' => 'Trainable Skill Location', 'game_map_id' => $map->id, 'type' => 1]);
        $skill = $this->createGameSkill(['name' => 'Trainable Skill', 'can_train' => true]);
        $this->expectException(RuntimeException::class);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name', 'crafting_skill_names']),
            collect(['Trainable Skill Profile', $map->name, $location->name, $skill->name]),
        ]));
    }

    public function test_malformed_range_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Malformed Range Map']);
        $location = $this->createLocation(['name' => 'Malformed Range Location', 'game_map_id' => $map->id, 'type' => 1]);
        $this->expectException(RuntimeException::class);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name', 'gold_gain_range']),
            collect(['Malformed Range Profile', $map->name, $location->name, 'not-a-range']),
        ]));
    }

    public function test_negative_range_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Negative Range Map']);
        $location = $this->createLocation(['name' => 'Negative Range Location', 'game_map_id' => $map->id, 'type' => 1]);
        $this->expectException(RuntimeException::class);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name', 'gold_gain_range']),
            collect(['Negative Range Profile', $map->name, $location->name, '-5-10']),
        ]));
    }

    public function test_malformed_legacy_combined_rarity_range_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Malformed Legacy Range Map']);
        $location = $this->createLocation(['name' => 'Malformed Legacy Range Location', 'game_map_id' => $map->id, 'type' => 1]);
        $this->expectException(RuntimeException::class);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name', 'unique_mythic_cosmic_item_drop_chance_increase_range']),
            collect(['Malformed Legacy Range Profile', $map->name, $location->name, '1--5']),
        ]));
    }

    public function test_unknown_crafting_skill_name_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Skill Map']);
        $location = $this->createLocation(['name' => 'Skill Location', 'game_map_id' => $map->id, 'type' => 1]);
        $this->expectException(RuntimeException::class);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name', 'crafting_skill_names']),
            collect(['Bad Skill Profile', $map->name, $location->name, 'Does Not Exist Skill']),
        ]));
    }

    public function test_atonement_label_resolves_to_gem_type_value(): void
    {
        $map = $this->createGameMap(['name' => 'Atonement Map']);
        $location = $this->createLocation(['name' => 'Atonement Location', 'game_map_id' => $map->id, 'type' => 1]);
        $label = GemTypeValue::getNames()[GemTypeValue::ICE];

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name', 'gem_world_name', 'monster_atonement']),
            collect(['Atonement Profile', $map->name, $location->name, 'The Atonement Hush', $label]),
        ]));

        $profile = GameLocationGemParamter::where('name', 'Atonement Profile')->first();

        $this->assertSame(GemTypeValue::ICE, $profile->monster_atonement);
    }

    public function test_fallback_combined_rarity_range_applies_to_unique_mythic_and_cosmic(): void
    {
        $map = $this->createGameMap(['name' => 'Fallback Map']);
        $location = $this->createLocation(['name' => 'Fallback Location', 'game_map_id' => $map->id, 'type' => 1]);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name', 'gem_world_name', 'unique_mythic_cosmic_item_drop_chance_increase_range']),
            collect(['Fallback Profile', $map->name, $location->name, 'The Fallback Hush', '0.01-0.02']),
        ]));

        $profile = GameLocationGemParamter::where('name', 'Fallback Profile')->first();

        $this->assertSame('0.01-0.02', $profile->unique_item_drop_chance_increase_range);
        $this->assertSame('0.01-0.02', $profile->mythic_item_drop_chance_increase_range);
        $this->assertSame('0.01-0.02', $profile->cosmic_item_drop_chance_increase_range);
    }

    public function test_blank_row_stops_processing_without_touching_rows_after_it(): void
    {
        $map = $this->createGameMap(['name' => 'Blank Stop Map']);
        $location = $this->createLocation(['name' => 'Blank Stop Location', 'game_map_id' => $map->id, 'type' => 1]);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name', 'gem_world_name']),
            collect(['Valid Before Blank Profile', $map->name, $location->name, 'The Valid Before Blank Hush']),
            collect([null, null, null, null]),
            collect(['Should Not Be Reached Profile', $map->name, $location->name, 'The Unreached Hush']),
        ]));

        $this->assertNotNull(GameLocationGemParamter::where('name', 'Valid Before Blank Profile')->first());
        $this->assertNull(GameLocationGemParamter::where('name', 'Should Not Be Reached Profile')->first());
    }

    public function test_zero_only_optional_range_cells_are_normalized_to_null(): void
    {
        $map = $this->createGameMap(['name' => 'Zero Range Map']);
        $location = $this->createLocation(['name' => 'Zero Range Location', 'game_map_id' => $map->id, 'type' => 1]);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name', 'gem_world_name', 'gold_gain_range', 'crafting_skill_bonus_range', 'item_drop_chance_increase_range', 'unique_item_drop_chance_increase_range']),
            collect(['Zero Range Profile', $map->name, $location->name, 'The Zero Range Hush', 0, 0.0, '0', '0-0']),
        ]));

        $profile = GameLocationGemParamter::where('name', 'Zero Range Profile')->first();

        $this->assertNull($profile->gold_gain_range);
        $this->assertNull($profile->crafting_skill_bonus_range);
        $this->assertNull($profile->item_drop_chance_increase_range);
        $this->assertNull($profile->unique_item_drop_chance_increase_range);
    }

    public function test_nonzero_scalar_range_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Nonzero Scalar Map']);
        $location = $this->createLocation(['name' => 'Nonzero Scalar Location', 'game_map_id' => $map->id, 'type' => 1]);
        $this->expectException(RuntimeException::class);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name', 'gold_gain_range']),
            collect(['Nonzero Scalar Profile', $map->name, $location->name, '5']),
        ]));
    }

    public function test_missing_gem_world_name_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Missing World Name Map']);
        $location = $this->createLocation(['name' => 'Missing World Name Location', 'game_map_id' => $map->id, 'type' => 1]);
        $this->expectException(RuntimeException::class);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name']),
            collect(['Missing World Name Profile', $map->name, $location->name]),
        ]));
    }

    public function test_gem_world_name_containing_the_location_name_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Containment Map']);
        $location = $this->createLocation(['name' => 'Abandonded Chapel', 'game_map_id' => $map->id, 'type' => 1]);
        $this->expectException(RuntimeException::class);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name', 'gem_world_name']),
            collect(['Containment Profile', $map->name, $location->name, 'The Abandonded Chapel Ruins']),
        ]));
    }

    public function test_location_with_live_manual_fight_reward_type_but_no_quest_item_is_eligible(): void
    {
        $map = $this->createGameMap(['name' => 'Live Handler Map']);
        $location = $this->createLocation([
            'name' => 'Live Handler Location',
            'game_map_id' => $map->id,
            'type' => LocationType::PURGATORY_SMITH_HOUSE->value,
        ]);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name', 'gem_world_name']),
            collect(['Live Handler Profile', $map->name, $location->name, 'The Forge Hush']),
        ]));

        $this->assertNotNull(GameLocationGemParamter::where('name', 'Live Handler Profile')->first());
    }

    public function test_location_with_special_type_and_no_live_handler_or_quest_item_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'No Handler Map']);
        $location = $this->createLocation([
            'name' => 'No Handler Location',
            'game_map_id' => $map->id,
            'type' => LocationType::TWISTED_GATE->value,
        ]);
        $this->expectException(RuntimeException::class);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name', 'gem_world_name']),
            collect(['No Handler Profile', $map->name, $location->name, 'The Gateless Hush']),
        ]));
    }

    public function test_gem_world_name_matching_a_map_gem_world_name_fails_the_import(): void
    {
        $map = $this->createGameMap(['name' => 'Cross Type Map']);
        $location = $this->createLocation(['name' => 'Cross Type Location', 'game_map_id' => $map->id, 'type' => 1]);
        $this->createGameMapGemParamter(['gem_world_name' => 'The Shared Requiem']);
        $this->expectException(RuntimeException::class);

        (new LocationGemsSheet)->collection(collect([
            collect(['name', 'game_map_name', 'location_name', 'gem_world_name']),
            collect(['Cross Type Profile', $map->name, $location->name, 'The Shared Requiem']),
        ]));
    }
}
