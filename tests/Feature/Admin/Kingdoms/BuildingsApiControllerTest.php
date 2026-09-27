<?php

namespace Tests\Feature\Admin\Kingdoms;

use App\Admin\Jobs\AssignNewKingdomBuildingsJob;
use App\Admin\Jobs\UpdateKingdomBuildings;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameBuildingUnit;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreatePassiveSkill;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class BuildingsApiControllerTest extends TestCase
{
    use CreateGameBuilding, CreateGameBuildingUnit, CreateGameUnit, CreatePassiveSkill, CreateRole, CreateUser, RefreshDatabase;

    public function test_index_rejects_unauthenticated_request(): void
    {
        $this->getJson('/api/admin/kingdoms/buildings')->assertUnauthorized();
    }

    public function test_index_rejects_non_admin_request(): void
    {
        $this->actingAs($this->createUser())->getJson('/api/admin/kingdoms/buildings')->assertForbidden();
    }

    public function test_index_search_filters_by_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameBuilding(['name' => 'Barracks']);
        $this->createGameBuilding(['name' => 'Farm']);

        $response = $this->actingAs($admin)->getJson('/api/admin/kingdoms/buildings?search_text=Barr');

        $this->assertSame(['Barracks'], array_column($response->json('data'), 'name'));
    }

    public function test_index_sorts_by_max_level_descending(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameBuilding(['name' => 'Small', 'max_level' => 5]);
        $this->createGameBuilding(['name' => 'Large', 'max_level' => 30]);

        $response = $this->actingAs($admin)->getJson('/api/admin/kingdoms/buildings?sort_key=max_level&sort_direction=desc');

        $this->assertSame('Large', $response->json('data.0.name'));
    }

    public function test_show_lists_recruitable_units_in_required_level_order(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $building = $this->createGameBuilding(['name' => 'Barracks', 'trains_units' => true]);
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);
        $archers = $this->createGameUnit(['name' => 'Archers']);
        $this->createGameBuildingUnit(['game_building_id' => $building->id, 'game_unit_id' => $archers->id, 'required_level' => 6]);
        $this->createGameBuildingUnit(['game_building_id' => $building->id, 'game_unit_id' => $spearmen->id, 'required_level' => 1]);

        $response = $this->actingAs($admin)->getJson('/api/admin/kingdoms/buildings/'.$building->id);

        $response->assertOk()->assertJson([
            'unit_ids' => [$spearmen->id, $archers->id],
            'units' => [
                ['unit_id' => $spearmen->id, 'unit_name' => 'Spearmen', 'required_level' => 1],
                ['unit_id' => $archers->id, 'unit_name' => 'Archers', 'required_level' => 6],
            ],
        ]);
    }

    public function test_options_return_passive_skills_and_units(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $passiveSkill = $this->createPassiveSkill(['name' => 'Blacksmiths Furnace']);
        $unit = $this->createGameUnit(['name' => 'Cavalry']);

        $response = $this->actingAs($admin)->getJson('/api/admin/kingdoms/buildings/options');

        $this->assertContains(['id' => $passiveSkill->id, 'name' => 'Blacksmiths Furnace'], $response->json('passive_skills'));
        $this->assertContains(['id' => $unit->id, 'name' => 'Cavalry'], $response->json('units'));
    }

    public function test_store_creates_the_selected_unit_relationships_with_per_level_progression(): void
    {
        Queue::fake();

        $admin = $this->createAdmin($this->createAdminRole());
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);
        $archers = $this->createGameUnit(['name' => 'Archers']);

        $response = $this->actingAs($admin)->postJson('/api/admin/kingdoms/buildings', [
            'name' => 'Barracks', 'description' => 'Trains soldiers.', 'max_level' => 30, 'base_durability' => 100,
            'base_defence' => 100, 'required_population' => 10, 'is_walls' => false, 'is_church' => false,
            'is_farm' => false, 'is_resource_building' => false, 'trains_units' => true, 'is_locked' => false,
            'is_special' => false, 'wood_cost' => 10, 'clay_cost' => 10, 'stone_cost' => 10, 'iron_cost' => 10,
            'steel_cost' => 0, 'increase_population_amount' => 0, 'increase_morale_amount' => 0,
            'decrease_morale_amount' => 0, 'increase_wood_amount' => 0, 'increase_clay_amount' => 0,
            'increase_stone_amount' => 0, 'increase_iron_amount' => 0, 'increase_durability_amount' => 10,
            'increase_defence_amount' => 10, 'time_to_build' => 5, 'time_increase_amount' => 0.1,
            'units_per_level' => 5, 'only_at_level' => null, 'passive_skill_id' => null, 'level_required' => null,
            'unit_ids' => [$archers->id, $spearmen->id],
        ]);

        $building = GameBuilding::where('name', 'Barracks')->first();

        $response->assertCreated()->assertJson(['unit_ids' => [$archers->id, $spearmen->id]]);
        $this->assertSame(
            [$archers->id => 1, $spearmen->id => 6],
            GameBuildingUnit::where('game_building_id', $building->id)->orderBy('required_level')->pluck('required_level', 'game_unit_id')->all()
        );
    }

    public function test_store_gives_the_new_building_to_existing_player_kingdoms(): void
    {
        Queue::fake();

        $admin = $this->createAdmin($this->createAdminRole());

        $this->actingAs($admin)->postJson('/api/admin/kingdoms/buildings', [
            'name' => 'Granary', 'description' => 'Stores food.', 'max_level' => 30, 'base_durability' => 100,
            'base_defence' => 100, 'required_population' => 10, 'is_walls' => false, 'is_church' => false,
            'is_farm' => false, 'is_resource_building' => false, 'trains_units' => false, 'is_locked' => false,
            'is_special' => false, 'wood_cost' => 10, 'clay_cost' => 10, 'stone_cost' => 10, 'iron_cost' => 10,
            'steel_cost' => 0, 'increase_population_amount' => 0, 'increase_morale_amount' => 0,
            'decrease_morale_amount' => 0, 'increase_wood_amount' => 0, 'increase_clay_amount' => 0,
            'increase_stone_amount' => 0, 'increase_iron_amount' => 0, 'increase_durability_amount' => 10,
            'increase_defence_amount' => 10, 'time_to_build' => 5, 'time_increase_amount' => 0.1,
            'units_per_level' => null, 'only_at_level' => null, 'passive_skill_id' => null, 'level_required' => null,
            'unit_ids' => [],
        ])->assertCreated();

        Queue::assertPushed(AssignNewKingdomBuildingsJob::class, function (AssignNewKingdomBuildingsJob $job): bool {
            return $job->gameBuilding->name === 'Granary';
        });
    }

    public function test_update_removes_relationships_missing_from_the_changed_selection(): void
    {
        Queue::fake();

        $admin = $this->createAdmin($this->createAdminRole());
        $building = $this->createGameBuilding(['name' => 'Barracks', 'trains_units' => true]);
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);
        $archers = $this->createGameUnit(['name' => 'Archers']);
        $this->createGameBuildingUnit(['game_building_id' => $building->id, 'game_unit_id' => $spearmen->id, 'required_level' => 1]);

        $this->actingAs($admin)->putJson('/api/admin/kingdoms/buildings/'.$building->id, [
            'name' => 'Barracks', 'description' => 'Trains soldiers.', 'max_level' => 30, 'base_durability' => 100,
            'base_defence' => 100, 'required_population' => 10, 'is_walls' => false, 'is_church' => false,
            'is_farm' => false, 'is_resource_building' => false, 'trains_units' => true, 'is_locked' => false,
            'is_special' => false, 'wood_cost' => 10, 'clay_cost' => 10, 'stone_cost' => 10, 'iron_cost' => 10,
            'steel_cost' => 0, 'increase_population_amount' => 0, 'increase_morale_amount' => 0,
            'decrease_morale_amount' => 0, 'increase_wood_amount' => 0, 'increase_clay_amount' => 0,
            'increase_stone_amount' => 0, 'increase_iron_amount' => 0, 'increase_durability_amount' => 10,
            'increase_defence_amount' => 10, 'time_to_build' => 5, 'time_increase_amount' => 0.1,
            'units_per_level' => null, 'only_at_level' => null, 'passive_skill_id' => null, 'level_required' => null,
            'unit_ids' => [$archers->id],
        ])->assertOk();

        $this->assertSame([$archers->id], GameBuildingUnit::where('game_building_id', $building->id)->pluck('game_unit_id')->all());
    }

    public function test_update_to_a_building_that_does_not_train_units_removes_its_relationships(): void
    {
        Queue::fake();

        $admin = $this->createAdmin($this->createAdminRole());
        $building = $this->createGameBuilding(['name' => 'Barracks', 'trains_units' => true, 'units_per_level' => 5]);
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);
        $this->createGameBuildingUnit(['game_building_id' => $building->id, 'game_unit_id' => $spearmen->id, 'required_level' => 1]);

        $this->actingAs($admin)->putJson('/api/admin/kingdoms/buildings/'.$building->id, [
            'name' => 'Barracks', 'description' => 'No longer trains.', 'max_level' => 30, 'base_durability' => 100,
            'base_defence' => 100, 'required_population' => 10, 'is_walls' => false, 'is_church' => false,
            'is_farm' => false, 'is_resource_building' => false, 'trains_units' => false, 'is_locked' => false,
            'is_special' => false, 'wood_cost' => 10, 'clay_cost' => 10, 'stone_cost' => 10, 'iron_cost' => 10,
            'steel_cost' => 0, 'increase_population_amount' => 0, 'increase_morale_amount' => 0,
            'decrease_morale_amount' => 0, 'increase_wood_amount' => 0, 'increase_clay_amount' => 0,
            'increase_stone_amount' => 0, 'increase_iron_amount' => 0, 'increase_durability_amount' => 10,
            'increase_defence_amount' => 10, 'time_to_build' => 5, 'time_increase_amount' => 0.1,
            'units_per_level' => 5, 'only_at_level' => null, 'passive_skill_id' => null, 'level_required' => null,
            'unit_ids' => [$spearmen->id],
        ])->assertOk();

        $this->assertSame(0, GameBuildingUnit::where('game_building_id', $building->id)->count());
        $this->assertNull($building->refresh()->units_per_level);
    }

    public function test_update_refreshes_existing_player_kingdom_buildings(): void
    {
        Queue::fake();

        $admin = $this->createAdmin($this->createAdminRole());
        $building = $this->createGameBuilding(['name' => 'Walls']);

        $this->actingAs($admin)->putJson('/api/admin/kingdoms/buildings/'.$building->id, [
            'name' => 'Walls', 'description' => 'Stronger walls.', 'max_level' => 30, 'base_durability' => 500,
            'base_defence' => 500, 'required_population' => 10, 'is_walls' => true, 'is_church' => false,
            'is_farm' => false, 'is_resource_building' => false, 'trains_units' => false, 'is_locked' => false,
            'is_special' => false, 'wood_cost' => 10, 'clay_cost' => 10, 'stone_cost' => 10, 'iron_cost' => 10,
            'steel_cost' => 0, 'increase_population_amount' => 0, 'increase_morale_amount' => 0,
            'decrease_morale_amount' => 0, 'increase_wood_amount' => 0, 'increase_clay_amount' => 0,
            'increase_stone_amount' => 0, 'increase_iron_amount' => 0, 'increase_durability_amount' => 10,
            'increase_defence_amount' => 10, 'time_to_build' => 5, 'time_increase_amount' => 0.1,
            'units_per_level' => null, 'only_at_level' => null, 'passive_skill_id' => null, 'level_required' => null,
            'unit_ids' => [],
        ])->assertOk();

        Queue::assertPushed(UpdateKingdomBuildings::class, function (UpdateKingdomBuildings $job) use ($building): bool {
            return $job->gameBuilding->id === $building->id;
        });
    }

    public function test_store_rejects_both_per_level_and_only_at_level_scheduling(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);

        $response = $this->actingAs($admin)->postJson('/api/admin/kingdoms/buildings', [
            'name' => 'Barracks', 'description' => 'Trains soldiers.', 'max_level' => 30, 'base_durability' => 100,
            'base_defence' => 100, 'required_population' => 10, 'is_walls' => false, 'is_church' => false,
            'is_farm' => false, 'is_resource_building' => false, 'trains_units' => true, 'is_locked' => false,
            'is_special' => false, 'wood_cost' => 10, 'clay_cost' => 10, 'stone_cost' => 10, 'iron_cost' => 10,
            'steel_cost' => 0, 'increase_population_amount' => 0, 'increase_morale_amount' => 0,
            'decrease_morale_amount' => 0, 'increase_wood_amount' => 0, 'increase_clay_amount' => 0,
            'increase_stone_amount' => 0, 'increase_iron_amount' => 0, 'increase_durability_amount' => 10,
            'increase_defence_amount' => 10, 'time_to_build' => 5, 'time_increase_amount' => 0.1,
            'units_per_level' => 5, 'only_at_level' => 10, 'passive_skill_id' => null, 'level_required' => null,
            'unit_ids' => [$spearmen->id],
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['units_per_level']);
    }

    public function test_store_rejects_a_unit_schedule_exceeding_the_max_level(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);
        $archers = $this->createGameUnit(['name' => 'Archers']);

        $response = $this->actingAs($admin)->postJson('/api/admin/kingdoms/buildings', [
            'name' => 'Barracks', 'description' => 'Trains soldiers.', 'max_level' => 2, 'base_durability' => 100,
            'base_defence' => 100, 'required_population' => 10, 'is_walls' => false, 'is_church' => false,
            'is_farm' => false, 'is_resource_building' => false, 'trains_units' => true, 'is_locked' => false,
            'is_special' => false, 'wood_cost' => 10, 'clay_cost' => 10, 'stone_cost' => 10, 'iron_cost' => 10,
            'steel_cost' => 0, 'increase_population_amount' => 0, 'increase_morale_amount' => 0,
            'decrease_morale_amount' => 0, 'increase_wood_amount' => 0, 'increase_clay_amount' => 0,
            'increase_stone_amount' => 0, 'increase_iron_amount' => 0, 'increase_durability_amount' => 10,
            'increase_defence_amount' => 10, 'time_to_build' => 5, 'time_increase_amount' => 0.1,
            'units_per_level' => 2, 'only_at_level' => null, 'passive_skill_id' => null, 'level_required' => null,
            'unit_ids' => [$spearmen->id, $archers->id],
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['units_per_level']);
    }

    public function test_store_accepts_a_unit_schedule_whose_generated_level_equals_the_max_level(): void
    {
        Queue::fake();

        $admin = $this->createAdmin($this->createAdminRole());
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);
        $archers = $this->createGameUnit(['name' => 'Archers']);

        $response = $this->actingAs($admin)->postJson('/api/admin/kingdoms/buildings', [
            'name' => 'Barracks', 'description' => 'Trains soldiers.', 'max_level' => 3, 'base_durability' => 100,
            'base_defence' => 100, 'required_population' => 10, 'is_walls' => false, 'is_church' => false,
            'is_farm' => false, 'is_resource_building' => false, 'trains_units' => true, 'is_locked' => false,
            'is_special' => false, 'wood_cost' => 10, 'clay_cost' => 10, 'stone_cost' => 10, 'iron_cost' => 10,
            'steel_cost' => 0, 'increase_population_amount' => 0, 'increase_morale_amount' => 0,
            'decrease_morale_amount' => 0, 'increase_wood_amount' => 0, 'increase_clay_amount' => 0,
            'increase_stone_amount' => 0, 'increase_iron_amount' => 0, 'increase_durability_amount' => 10,
            'increase_defence_amount' => 10, 'time_to_build' => 5, 'time_increase_amount' => 0.1,
            'units_per_level' => 2, 'only_at_level' => null, 'passive_skill_id' => null, 'level_required' => null,
            'unit_ids' => [$spearmen->id, $archers->id],
        ]);

        $response->assertCreated();
        $building = GameBuilding::where('name', 'Barracks')->first();
        $this->assertSame([1, 3], GameBuildingUnit::where('game_building_id', $building->id)->orderBy('required_level')->pluck('required_level')->all());
    }

    public function test_store_accepts_only_at_level_equal_to_the_max_level(): void
    {
        Queue::fake();

        $admin = $this->createAdmin($this->createAdminRole());
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);

        $response = $this->actingAs($admin)->postJson('/api/admin/kingdoms/buildings', [
            'name' => 'Barracks', 'description' => 'Trains soldiers.', 'max_level' => 3, 'base_durability' => 100,
            'base_defence' => 100, 'required_population' => 10, 'is_walls' => false, 'is_church' => false,
            'is_farm' => false, 'is_resource_building' => false, 'trains_units' => true, 'is_locked' => false,
            'is_special' => false, 'wood_cost' => 10, 'clay_cost' => 10, 'stone_cost' => 10, 'iron_cost' => 10,
            'steel_cost' => 0, 'increase_population_amount' => 0, 'increase_morale_amount' => 0,
            'decrease_morale_amount' => 0, 'increase_wood_amount' => 0, 'increase_clay_amount' => 0,
            'increase_stone_amount' => 0, 'increase_iron_amount' => 0, 'increase_durability_amount' => 10,
            'increase_defence_amount' => 10, 'time_to_build' => 5, 'time_increase_amount' => 0.1,
            'units_per_level' => null, 'only_at_level' => 3, 'passive_skill_id' => null, 'level_required' => null,
            'unit_ids' => [$spearmen->id],
        ]);

        $response->assertCreated();
    }

    public function test_store_rejects_only_at_level_above_the_max_level(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);

        $response = $this->actingAs($admin)->postJson('/api/admin/kingdoms/buildings', [
            'name' => 'Barracks', 'description' => 'Trains soldiers.', 'max_level' => 3, 'base_durability' => 100,
            'base_defence' => 100, 'required_population' => 10, 'is_walls' => false, 'is_church' => false,
            'is_farm' => false, 'is_resource_building' => false, 'trains_units' => true, 'is_locked' => false,
            'is_special' => false, 'wood_cost' => 10, 'clay_cost' => 10, 'stone_cost' => 10, 'iron_cost' => 10,
            'steel_cost' => 0, 'increase_population_amount' => 0, 'increase_morale_amount' => 0,
            'decrease_morale_amount' => 0, 'increase_wood_amount' => 0, 'increase_clay_amount' => 0,
            'increase_stone_amount' => 0, 'increase_iron_amount' => 0, 'increase_durability_amount' => 10,
            'increase_defence_amount' => 10, 'time_to_build' => 5, 'time_increase_amount' => 0.1,
            'units_per_level' => null, 'only_at_level' => 4, 'passive_skill_id' => null, 'level_required' => null,
            'unit_ids' => [$spearmen->id],
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['only_at_level']);
    }

    public function test_store_rejects_a_training_building_without_units(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->postJson('/api/admin/kingdoms/buildings', [
            'name' => 'Barracks', 'description' => 'Trains soldiers.', 'max_level' => 30, 'base_durability' => 100,
            'base_defence' => 100, 'required_population' => 10, 'is_walls' => false, 'is_church' => false,
            'is_farm' => false, 'is_resource_building' => false, 'trains_units' => true, 'is_locked' => false,
            'is_special' => false, 'wood_cost' => 10, 'clay_cost' => 10, 'stone_cost' => 10, 'iron_cost' => 10,
            'steel_cost' => 0, 'increase_population_amount' => 0, 'increase_morale_amount' => 0,
            'decrease_morale_amount' => 0, 'increase_wood_amount' => 0, 'increase_clay_amount' => 0,
            'increase_stone_amount' => 0, 'increase_iron_amount' => 0, 'increase_durability_amount' => 10,
            'increase_defence_amount' => 10, 'time_to_build' => 5, 'time_increase_amount' => 0.1,
            'units_per_level' => null, 'only_at_level' => null, 'passive_skill_id' => null, 'level_required' => null,
            'unit_ids' => [],
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['unit_ids']);
    }

    public function test_store_clears_the_resource_flag_when_no_resource_increases(): void
    {
        Queue::fake();

        $admin = $this->createAdmin($this->createAdminRole());

        $this->actingAs($admin)->postJson('/api/admin/kingdoms/buildings', [
            'name' => 'Idle Mine', 'description' => 'Produces nothing.', 'max_level' => 30, 'base_durability' => 100,
            'base_defence' => 100, 'required_population' => 10, 'is_walls' => false, 'is_church' => false,
            'is_farm' => false, 'is_resource_building' => true, 'trains_units' => false, 'is_locked' => false,
            'is_special' => false, 'wood_cost' => 10, 'clay_cost' => 10, 'stone_cost' => 10, 'iron_cost' => 10,
            'steel_cost' => 0, 'increase_population_amount' => 0, 'increase_morale_amount' => 0,
            'decrease_morale_amount' => 0, 'increase_wood_amount' => 0, 'increase_clay_amount' => 0,
            'increase_stone_amount' => 0, 'increase_iron_amount' => 0, 'increase_durability_amount' => 10,
            'increase_defence_amount' => 10, 'time_to_build' => 5, 'time_increase_amount' => 0.1,
            'units_per_level' => null, 'only_at_level' => null, 'passive_skill_id' => null, 'level_required' => null,
            'unit_ids' => [],
        ])->assertCreated();

        $this->assertFalse(GameBuilding::where('name', 'Idle Mine')->first()->is_resource_building);
    }
}
