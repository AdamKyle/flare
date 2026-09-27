<?php

namespace Tests\Feature\Admin\Kingdoms;

use App\Flare\Models\GameUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameBuildingUnit;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class UnitsApiControllerTest extends TestCase
{
    use CreateGameBuilding, CreateGameBuildingUnit, CreateGameUnit, CreateRole, CreateUser, RefreshDatabase;

    public function test_index_rejects_unauthenticated_request(): void
    {
        $this->getJson('/api/admin/kingdoms/units')->assertUnauthorized();
    }

    public function test_index_rejects_non_admin_request(): void
    {
        $this->actingAs($this->createUser())->getJson('/api/admin/kingdoms/units')->assertForbidden();
    }

    public function test_index_search_filters_by_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameUnit(['name' => 'Spearmen']);
        $this->createGameUnit(['name' => 'Archers']);

        $response = $this->actingAs($admin)->getJson('/api/admin/kingdoms/units?search_text=Arch');

        $this->assertSame(['Archers'], array_column($response->json('data'), 'name'));
    }

    public function test_index_sorts_by_attack_descending(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameUnit(['name' => 'Weak', 'attack' => 1]);
        $this->createGameUnit(['name' => 'Strong', 'attack' => 500]);

        $response = $this->actingAs($admin)->getJson('/api/admin/kingdoms/units?sort_key=attack&sort_direction=desc');

        $this->assertSame('Strong', $response->json('data.0.name'));
    }

    public function test_show_returns_an_empty_recruiting_building_list_for_an_unassigned_unit(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $unit = $this->createGameUnit(['name' => 'Unassigned']);

        $response = $this->actingAs($admin)->getJson('/api/admin/kingdoms/units/'.$unit->id);

        $response->assertOk()->assertJson(['name' => 'Unassigned', 'recruited_from' => []]);
    }

    public function test_show_lists_every_building_that_recruits_the_unit(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $unit = $this->createGameUnit(['name' => 'Settler']);
        $church = $this->createGameBuilding(['name' => 'Church', 'trains_units' => true]);
        $town = $this->createGameBuilding(['name' => 'Town Hall', 'trains_units' => true]);
        $this->createGameBuildingUnit(['game_building_id' => $town->id, 'game_unit_id' => $unit->id, 'required_level' => 10]);
        $this->createGameBuildingUnit(['game_building_id' => $church->id, 'game_unit_id' => $unit->id, 'required_level' => 1]);

        $response = $this->actingAs($admin)->getJson('/api/admin/kingdoms/units/'.$unit->id);

        $response->assertJson(['recruited_from' => [
            ['building_id' => $church->id, 'building_name' => 'Church', 'required_level' => 1],
            ['building_id' => $town->id, 'building_name' => 'Town Hall', 'required_level' => 10],
        ]]);
    }

    public function test_edit_returns_current_form_values(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $unit = $this->createGameUnit(['name' => 'Edit Target', 'time_to_recruit' => 30]);

        $response = $this->actingAs($admin)->getJson('/api/admin/kingdoms/units/'.$unit->id.'/edit');

        $response->assertOk()->assertJson(['name' => 'Edit Target', 'time_to_recruit' => 30]);
    }

    public function test_store_creates_a_unit(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->postJson('/api/admin/kingdoms/units', [
            'name' => 'Clerics', 'description' => 'Heal units.', 'attack' => 1, 'defence' => 5,
            'can_heal' => true, 'heal_percentage' => 0.05, 'siege_weapon' => false, 'is_airship' => false,
            'attacker' => false, 'defender' => true, 'can_not_be_healed' => false, 'is_settler' => false,
            'is_special' => false, 'reduces_morale_by' => null, 'wood_cost' => 10, 'clay_cost' => 10,
            'stone_cost' => 10, 'iron_cost' => 10, 'steel_cost' => 0, 'required_population' => 2,
            'time_to_recruit' => 60,
        ]);

        $response->assertCreated();
        $this->assertSame(0.05, GameUnit::where('name', 'Clerics')->first()->heal_percentage);
    }

    public function test_update_persists_unit_flags(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $unit = $this->createGameUnit(['name' => 'Settler']);

        $this->actingAs($admin)->putJson('/api/admin/kingdoms/units/'.$unit->id, [
            'name' => 'Settler', 'description' => 'Settles kingdoms.', 'attack' => 0, 'defence' => 0,
            'can_heal' => false, 'heal_percentage' => null, 'siege_weapon' => false, 'is_airship' => false,
            'attacker' => false, 'defender' => false, 'can_not_be_healed' => true, 'is_settler' => true,
            'is_special' => true, 'reduces_morale_by' => 0.1, 'wood_cost' => 10, 'clay_cost' => 10,
            'stone_cost' => 10, 'iron_cost' => 10, 'steel_cost' => 0, 'required_population' => 2,
            'time_to_recruit' => 60,
        ])->assertOk();

        $unit->refresh();

        $this->assertTrue($unit->is_settler);
        $this->assertTrue($unit->can_not_be_healed);
        $this->assertSame(0.1, $unit->reduces_morale_by);
    }

    public function test_store_rejects_a_missing_recruitment_time(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->postJson('/api/admin/kingdoms/units', [
            'name' => 'Clerics', 'description' => 'Heal units.', 'attack' => 1, 'defence' => 5,
            'can_heal' => true, 'siege_weapon' => false, 'is_airship' => false, 'attacker' => false,
            'defender' => true, 'can_not_be_healed' => false, 'is_settler' => false, 'is_special' => false,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['time_to_recruit']);
    }
}
