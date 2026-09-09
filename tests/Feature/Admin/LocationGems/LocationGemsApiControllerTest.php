<?php

namespace Tests\Feature\Admin\LocationGems;

use App\Admin\LocationGems\Imports\LocationGemsImport;
use App\Flare\Models\Gem;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use App\Game\Monsters\Values\MonsterCacheKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateGameLocationGemParamter;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class LocationGemsApiControllerTest extends TestCase
{
    use CreateGameLocationGemParamter, CreateGameMap, CreateGameSkill, CreateGem, CreateLocation, CreateRole, CreateUser, RefreshDatabase;

    public function test_map_filter_only_returns_profiles_for_that_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $mapA = $this->createGameMap(['name' => 'Filter Map A']);
        $mapB = $this->createGameMap(['name' => 'Filter Map B']);
        $locationA = $this->createLocation(['name' => 'Filter Location A', 'game_map_id' => $mapA->id, 'type' => 1]);
        $locationB = $this->createLocation(['name' => 'Filter Location B', 'game_map_id' => $mapB->id, 'type' => 1]);
        $this->createGameLocationGemParamter(['name' => 'Profile A', 'location_id' => $locationA->id]);
        $this->createGameLocationGemParamter(['name' => 'Profile B', 'location_id' => $locationB->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/location-gems?filters[game_map_id]='.$mapA->id);
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertContains('Profile A', $names);
        $this->assertNotContains('Profile B', $names);
    }

    public function test_location_filter_only_returns_profile_for_that_location(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $map = $this->createGameMap(['name' => 'Location Filter Map']);
        $locationA = $this->createLocation(['name' => 'Location Filter Location A', 'game_map_id' => $map->id, 'type' => 1]);
        $locationB = $this->createLocation(['name' => 'Location Filter Location B', 'game_map_id' => $map->id, 'type' => 1]);
        $this->createGameLocationGemParamter(['name' => 'Location Profile A', 'location_id' => $locationA->id]);
        $this->createGameLocationGemParamter(['name' => 'Location Profile B', 'location_id' => $locationB->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/location-gems?filters[location_id]='.$locationA->id);
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertContains('Location Profile A', $names);
        $this->assertNotContains('Location Profile B', $names);
    }

    public function test_map_and_location_filters_compose(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $map = $this->createGameMap(['name' => 'Compose Filter Map']);
        $otherMap = $this->createGameMap(['name' => 'Compose Other Map']);
        $location = $this->createLocation(['name' => 'Compose Location', 'game_map_id' => $map->id, 'type' => 1]);
        $otherLocation = $this->createLocation(['name' => 'Compose Other Location', 'game_map_id' => $otherMap->id, 'type' => 1]);
        $this->createGameLocationGemParamter(['name' => 'Compose Profile', 'location_id' => $location->id]);
        $this->createGameLocationGemParamter(['name' => 'Compose Other Profile', 'location_id' => $otherLocation->id]);

        $response = $this->actingAs($admin)->call(
            'GET',
            '/api/admin/location-gems?filters[game_map_id]='.$map->id.'&filters[location_id]='.$location->id
        );
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertContains('Compose Profile', $names);
        $this->assertNotContains('Compose Other Profile', $names);
    }

    public function test_mismatched_map_and_location_filters_return_zero_rows(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $map = $this->createGameMap(['name' => 'Mismatch Filter Map']);
        $otherMap = $this->createGameMap(['name' => 'Mismatch Other Map']);
        $location = $this->createLocation(['name' => 'Mismatch Location', 'game_map_id' => $otherMap->id, 'type' => 1]);
        $this->createGameLocationGemParamter(['name' => 'Mismatch Profile', 'location_id' => $location->id]);

        $response = $this->actingAs($admin)->call(
            'GET',
            '/api/admin/location-gems?filters[game_map_id]='.$map->id.'&filters[location_id]='.$location->id
        );
        $data = json_decode($response->getContent(), true);

        $this->assertSame([], $data['data']);
    }

    public function test_sorting_by_name_orders_results(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameLocationGemParamter(['name' => 'Zeta Profile']);
        $this->createGameLocationGemParamter(['name' => 'Alpha Profile']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/location-gems?sort_key=name&sort_direction=asc');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertSame('Alpha Profile', $names[0]);
    }

    public function test_sorting_by_roll_count_orders_results(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameLocationGemParamter(['name' => 'Low Roll Profile', 'roll_count' => 1]);
        $this->createGameLocationGemParamter(['name' => 'High Roll Profile', 'roll_count' => 5]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/location-gems?sort_key=roll_count&sort_direction=desc');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertSame('High Roll Profile', $names[0]);
    }

    public function test_update_persists_changed_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Old Profile Name']);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/location-gems/'.$profile->id, [
            'location_id' => $profile->location_id,
            'name' => 'New Profile Name',
        ]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('New Profile Name', $data['name']);
        $this->assertDatabaseHas('game_location_gem_paramters', ['id' => $profile->id, 'name' => 'New Profile Name']);
    }

    public function test_store_rejects_ineligible_location(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $map = $this->createGameMap(['name' => 'Ineligible Store Map']);
        $location = $this->createLocation(['name' => 'Ineligible Store Location', 'game_map_id' => $map->id, 'type' => null]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/location-gems', [
            'location_id' => $location->id,
            'name' => 'Ineligible Store Profile',
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_update_rejects_ineligible_location(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Update Ineligible Profile']);
        $map = $this->createGameMap(['name' => 'Ineligible Update Map']);
        $ineligibleLocation = $this->createLocation(['name' => 'Ineligible Update Location', 'game_map_id' => $map->id, 'type' => null]);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/location-gems/'.$profile->id, [
            'location_id' => $ineligibleLocation->id,
            'name' => 'Update Ineligible Profile',
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_store_rejects_location_on_generated_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $generatedMap = $this->createGameMap(['name' => 'Generated Store Map', 'generated_map_type' => 'gem-world']);
        $generatedLocation = $this->createLocation(['name' => 'Generated Store Location', 'game_map_id' => $generatedMap->id, 'type' => 1]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/location-gems', [
            'location_id' => $generatedLocation->id,
            'name' => 'Generated Store Profile',
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_update_rejects_location_on_generated_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Update Generated Profile']);
        $generatedMap = $this->createGameMap(['name' => 'Generated Update Map', 'generated_map_type' => 'gem-world']);
        $generatedLocation = $this->createLocation(['name' => 'Generated Update Location', 'game_map_id' => $generatedMap->id, 'type' => 1]);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/location-gems/'.$profile->id, [
            'location_id' => $generatedLocation->id,
            'name' => 'Update Generated Profile',
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_store_rejects_trainable_crafting_skill(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $map = $this->createGameMap(['name' => 'Trainable Skill Map']);
        $location = $this->createLocation(['name' => 'Trainable Skill Location', 'game_map_id' => $map->id, 'type' => 1]);
        $skill = $this->createGameSkill(['name' => 'Trainable Skill', 'can_train' => true]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/location-gems', [
            'location_id' => $location->id,
            'name' => 'Trainable Skill Profile',
            'crafting_skill_ids' => [$skill->id],
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_update_rejects_trainable_crafting_skill(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Update Skill Profile']);
        $skill = $this->createGameSkill(['name' => 'Update Trainable Skill', 'can_train' => true]);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/location-gems/'.$profile->id, [
            'location_id' => $profile->location_id,
            'name' => 'Update Skill Profile',
            'crafting_skill_ids' => [$skill->id],
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_generated_map_association_present_in_detail_when_relationship_exists(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'World Profile']);
        $generatedMap = $this->createGameMap([
            'name' => 'Generated World',
            'generated_map_type' => 'gem-world',
            'game_location_gem_paramter_id' => $profile->id,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/location-gems/'.$profile->id);
        $data = json_decode($response->getContent(), true);

        $this->assertNotNull($data['generated_gem_world']);
        $this->assertSame($generatedMap->id, $data['generated_gem_world']['id']);
        $this->assertSame('Generated World', $data['generated_gem_world']['name']);
    }

    public function test_generated_map_association_includes_parent_map_id_when_present(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'World Parent Profile']);
        $parentMap = $this->createGameMap(['name' => 'Parent Surface Map']);
        $this->createGameMap([
            'name' => 'Generated World With Parent',
            'generated_map_type' => 'gem-world',
            'game_location_gem_paramter_id' => $profile->id,
            'generated_parent_game_map_id' => $parentMap->id,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/location-gems/'.$profile->id);
        $data = json_decode($response->getContent(), true);

        $this->assertNotNull($data['generated_gem_world']['parent_map']);
        $this->assertSame($parentMap->id, $data['generated_gem_world']['parent_map']['id']);
    }

    public function test_import_invokes_the_location_gems_workbook_boundary(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $file = UploadedFile::fake()->create('location-gems.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        Excel::shouldReceive('import')->once()->with(Mockery::type(LocationGemsImport::class), $file);

        $response = $this->actingAs($admin)->post('/api/admin/location-gems/import', ['location_gems_import' => $file]);

        $response->assertOk()->assertJson(['message' => 'Location Gems imported successfully.']);
    }

    public function test_index_rejects_unauthenticated_request(): void
    {
        $response = $this->call('GET', '/api/admin/location-gems', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_index_rejects_non_admin_request(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/location-gems', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_search_filters_by_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameLocationGemParamter(['name' => 'Findable Profile']);
        $this->createGameLocationGemParamter(['name' => 'Other Profile']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/location-gems?search_text=Findable');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertContains('Findable Profile', $names);
        $this->assertNotContains('Other Profile', $names);
    }

    public function test_show_returns_profile_detail_without_character_power_reduction(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Detail Profile']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/location-gems/'.$profile->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayNotHasKey('character_power_reduction_range', $data['ranges']);
    }

    public function test_generated_gem_world_locations_are_not_selectable_as_source_profiles(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $generatedMap = $this->createGameMap(['name' => 'Generated Location Map', 'generated_map_type' => 'gem-world']);
        $generatedLocation = $this->createLocation([
            'name' => 'Generated Gem World Location',
            'game_map_id' => $generatedMap->id,
            'type' => 0,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/location-gems/options');
        $data = json_decode($response->getContent(), true);
        $locationIds = array_column($data['locations'], 'value');

        $this->assertNotContains($generatedLocation->id, $locationIds);
    }

    public function test_roll_endpoint_calls_existing_roll_behavior_and_returns_updated_detail(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Rollable Profile', 'roll_count' => 0]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/location-gems/'.$profile->id.'/roll');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotNull($data['rolled_gem']);
        $this->assertSame(1, $data['roll_count']);
        $this->assertDatabaseHas('gems', ['name' => $profile->name, 'roll_number' => 1]);
    }

    public function test_single_roll_invalidates_gem_affected_monster_caches(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Cache Invalidation Profile']);

        resolve(BuildMonsterCacheService::class)->buildAll();

        $this->actingAs($admin)->call('POST', '/api/admin/location-gems/'.$profile->id.'/roll');

        $this->assertFalse(Cache::has(MonsterCacheKey::MONSTERS->value));
        $this->assertFalse(Cache::has(MonsterCacheKey::LOCATION_MONSTERS->value));
        $this->assertTrue(Cache::has(MonsterCacheKey::RAID_MONSTERS->value));
    }

    public function test_roll_all_rolls_a_profile_without_a_rolled_gem(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Unrolled Profile', 'roll_count' => 0]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/location-gems/roll-all');
        $data = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertSame(1, $data['rolled_count']);
        $this->assertArrayNotHasKey('skipped_count', $data);
        $this->assertArrayNotHasKey('skipped', $data);
        $this->assertSame($profile->id, $data['rolled'][0]['profile_id']);
        $this->assertSame(1, $data['rolled'][0]['rolled_gem']['roll_number']);
        $this->assertTrue($data['rolled'][0]['rolled_gem']['is_active']);
        $this->assertNotNull($profile->refresh()->rolled_gem_id);
    }

    public function test_roll_all_creates_a_new_gem_for_a_profile_with_an_existing_active_roll(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Already Rolled Profile']);
        $existingGem = $this->createLocationGeneratedGem($profile, ['name' => 'Existing Gem', 'roll_number' => 3]);
        $profile->update(['rolled_gem_id' => $existingGem->id, 'roll_count' => 3]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/location-gems/roll-all');
        $data = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertSame(1, $data['rolled_count']);
        $this->assertNotNull(Gem::find($existingGem->id));
        $this->assertNotSame($existingGem->id, $data['rolled'][0]['rolled_gem']['id']);
        $this->assertSame(4, $data['rolled'][0]['rolled_gem']['roll_number']);
        $this->assertSame(4, $profile->refresh()->roll_count);
        $this->assertSame($data['rolled'][0]['rolled_gem']['id'], $profile->rolled_gem_id);
    }

    public function test_roll_all_rolls_profiles_with_stored_zero_only_ranges(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $zeroRangeProfile = $this->createGameLocationGemParamter([
            'name' => 'Zero Range Profile',
            'roll_count' => 0,
            'gold_gain_range' => '0',
            'character_xp_bonus_range' => '0.4321-0.4321',
        ]);
        $normalProfile = $this->createGameLocationGemParamter(['name' => 'Normal Profile', 'roll_count' => 0]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/location-gems/roll-all');
        $data = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertSame(2, $data['rolled_count']);
        $this->assertArrayNotHasKey('skipped_count', $data);
        $this->assertArrayNotHasKey('skipped', $data);
        $zeroRangeRow = collect($data['rolled'])->firstWhere('profile_id', $zeroRangeProfile->id);
        $this->assertNotNull($zeroRangeProfile->refresh()->rolled_gem_id);
        $this->assertNotNull($normalProfile->refresh()->rolled_gem_id);
        $this->assertSame(1, $zeroRangeProfile->roll_count);
        $this->assertSame(1, $normalProfile->roll_count);
        $this->assertNull($zeroRangeRow['rolled_gem']['gold_gain']);
        $this->assertSame(0.4321, $zeroRangeRow['rolled_gem']['character_xp_bonus']);
    }

    public function test_roll_all_invalidates_gem_affected_monster_caches_when_rerolling(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Reroll Cache Profile']);
        $existingGem = $this->createLocationGeneratedGem($profile, ['name' => 'Reroll Cache Gem']);
        $profile->update(['rolled_gem_id' => $existingGem->id, 'roll_count' => 1]);

        resolve(BuildMonsterCacheService::class)->buildAll();

        $this->actingAs($admin)->call('POST', '/api/admin/location-gems/roll-all');

        $this->assertFalse(Cache::has(MonsterCacheKey::MONSTERS->value));
        $this->assertFalse(Cache::has(MonsterCacheKey::LOCATION_MONSTERS->value));
    }

    public function test_roll_all_succeeds_when_a_generated_gem_world_already_exists(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'World Backed Profile']);
        $existingGem = $this->createLocationGeneratedGem($profile, ['name' => 'World Backed Gem']);
        $profile->update(['rolled_gem_id' => $existingGem->id, 'roll_count' => 1]);
        $this->createGameMap([
            'name' => 'Roll All World',
            'generated_map_type' => 'gem-world',
            'game_location_gem_paramter_id' => $profile->id,
        ]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/location-gems/roll-all');
        $data = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertSame(1, $data['rolled_count']);
        $this->assertSame(2, $profile->refresh()->roll_count);
        $this->assertNotSame($existingGem->id, $profile->rolled_gem_id);
    }

    public function test_detail_no_longer_includes_roll_history(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'History Profile']);
        $secondGem = $this->createLocationGeneratedGem($profile, ['name' => 'History Gem 2', 'roll_number' => 2]);
        $profile->update(['rolled_gem_id' => $secondGem->id, 'roll_count' => 2]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/location-gems/'.$profile->id);
        $data = json_decode($response->getContent(), true);

        $this->assertArrayNotHasKey('roll_history', $data);
        $this->assertTrue($data['rolled_gem']['is_active']);
        $this->assertSame($secondGem->id, $data['rolled_gem']['id']);
    }

    public function test_paginated_rolls_endpoint_returns_every_roll_active_first(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'History Profile']);
        $firstGem = $this->createLocationGeneratedGem($profile, ['name' => 'History Gem 1', 'roll_number' => 1]);
        $secondGem = $this->createLocationGeneratedGem($profile, ['name' => 'History Gem 2', 'roll_number' => 2]);
        $profile->update(['rolled_gem_id' => $secondGem->id, 'roll_count' => 2]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/location-gems/'.$profile->id.'/rolls');
        $data = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertCount(2, $data['data']);
        $this->assertSame($secondGem->id, $data['data'][0]['id']);
        $this->assertTrue($data['data'][0]['is_active']);
        $this->assertSame($firstGem->id, $data['data'][1]['id']);
        $this->assertFalse($data['data'][1]['is_active']);
        $this->assertSame(10, $data['meta']['pagination']['per_page']);
        $this->assertSame(2, $data['meta']['pagination']['total']);
    }

    public function test_activate_roll_switches_active_gem_without_changing_roll_count(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Activation Profile']);
        $firstGem = $this->createLocationGeneratedGem($profile, ['name' => 'Activation Gem 1', 'roll_number' => 1]);
        $secondGem = $this->createLocationGeneratedGem($profile, ['name' => 'Activation Gem 2', 'roll_number' => 2]);
        $profile->update(['rolled_gem_id' => $secondGem->id, 'roll_count' => 2]);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/location-gems/'.$profile->id.'/rolls/'.$firstGem->id.'/activate');
        $data = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertSame($firstGem->id, $data['rolled_gem']['id']);
        $this->assertSame($firstGem->id, $profile->refresh()->rolled_gem_id);
        $this->assertSame(2, $profile->roll_count);
        $this->assertNotNull(Gem::find($secondGem->id));
    }

    public function test_activate_roll_invalidates_gem_affected_monster_caches(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Activation Cache Profile']);
        $firstGem = $this->createLocationGeneratedGem($profile, ['name' => 'Activation Cache Gem 1', 'roll_number' => 1]);
        $secondGem = $this->createLocationGeneratedGem($profile, ['name' => 'Activation Cache Gem 2', 'roll_number' => 2]);
        $profile->update(['rolled_gem_id' => $secondGem->id, 'roll_count' => 2]);

        resolve(BuildMonsterCacheService::class)->buildAll();

        $this->actingAs($admin)->call('PUT', '/api/admin/location-gems/'.$profile->id.'/rolls/'.$firstGem->id.'/activate');

        $this->assertFalse(Cache::has(MonsterCacheKey::MONSTERS->value));
        $this->assertFalse(Cache::has(MonsterCacheKey::LOCATION_MONSTERS->value));
    }

    public function test_activate_roll_succeeds_when_a_generated_gem_world_exists(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Activation World Profile']);
        $firstGem = $this->createLocationGeneratedGem($profile, ['name' => 'Activation World Gem 1', 'roll_number' => 1]);
        $secondGem = $this->createLocationGeneratedGem($profile, ['name' => 'Activation World Gem 2', 'roll_number' => 2]);
        $profile->update(['rolled_gem_id' => $secondGem->id, 'roll_count' => 2]);
        $this->createGameMap([
            'name' => 'Activation World',
            'generated_map_type' => 'gem-world',
            'game_location_gem_paramter_id' => $profile->id,
        ]);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/location-gems/'.$profile->id.'/rolls/'.$firstGem->id.'/activate');

        $response->assertStatus(200);
        $this->assertSame($firstGem->id, $profile->refresh()->rolled_gem_id);
    }

    public function test_activate_roll_rejects_gem_from_another_profile(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Owner Profile']);
        $otherProfile = $this->createGameLocationGemParamter(['name' => 'Other Profile']);
        $activeGem = $this->createLocationGeneratedGem($profile, ['name' => 'Owner Gem', 'roll_number' => 1]);
        $profile->update(['rolled_gem_id' => $activeGem->id, 'roll_count' => 1]);
        $foreignGem = $this->createLocationGeneratedGem($otherProfile, ['name' => 'Foreign Gem', 'roll_number' => 1]);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/location-gems/'.$profile->id.'/rolls/'.$foreignGem->id.'/activate');

        $response->assertStatus(422);
        $this->assertSame($activeGem->id, $profile->refresh()->rolled_gem_id);
    }

    public function test_activate_roll_rejects_gem_from_wrong_domain(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Wrong Domain Profile']);
        $activeGem = $this->createLocationGeneratedGem($profile, ['name' => 'Wrong Domain Active Gem', 'roll_number' => 1]);
        $profile->update(['rolled_gem_id' => $activeGem->id, 'roll_count' => 1]);
        $mapGem = $this->createGem(['name' => 'Wrong Domain Map Gem', 'domain' => 'map']);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/location-gems/'.$profile->id.'/rolls/'.$mapGem->id.'/activate');

        $response->assertStatus(422);
        $this->assertSame($activeGem->id, $profile->refresh()->rolled_gem_id);
    }

    public function test_store_persists_valid_location_gem_profile(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $map = $this->createGameMap(['name' => 'Store Map']);
        $location = $this->createLocation(['name' => 'Store Location', 'game_map_id' => $map->id, 'type' => 1]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/location-gems', [
            'location_id' => $location->id,
            'name' => 'Created Profile',
            'description' => 'A profile.',
        ]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Created Profile', $data['name']);
        $this->assertDatabaseHas('game_location_gem_paramters', ['name' => 'Created Profile']);
    }

    public function test_store_rejects_invalid_range_format(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $map = $this->createGameMap(['name' => 'Invalid Range Map']);
        $location = $this->createLocation(['name' => 'Invalid Range Location', 'game_map_id' => $map->id, 'type' => 1]);

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/location-gems',
            [
                'location_id' => $location->id,
                'name' => 'Invalid Range Profile',
                'gold_gain_range' => 'not-a-range',
            ],
            [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_store_rejects_range_with_minimum_above_maximum(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $map = $this->createGameMap(['name' => 'Inverted Range Map']);
        $location = $this->createLocation(['name' => 'Inverted Range Location', 'game_map_id' => $map->id, 'type' => 1]);

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/location-gems',
            [
                'location_id' => $location->id,
                'name' => 'Inverted Range Profile',
                'gold_gain_range' => '0.5-0.2',
            ],
            [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_store_normalizes_blank_range_to_null(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $map = $this->createGameMap(['name' => 'Blank Range Map']);
        $location = $this->createLocation(['name' => 'Blank Range Location', 'game_map_id' => $map->id, 'type' => 1]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/location-gems', [
            'location_id' => $location->id,
            'name' => 'Blank Range Profile',
            'gold_gain_range' => '',
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertDatabaseHas('game_location_gem_paramters', [
            'name' => 'Blank Range Profile',
            'gold_gain_range' => null,
        ]);
    }

    public function test_store_normalizes_zero_range_to_null(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $map = $this->createGameMap(['name' => 'Zero Range Map']);
        $location = $this->createLocation(['name' => 'Zero Range Location', 'game_map_id' => $map->id, 'type' => 1]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/location-gems', [
            'location_id' => $location->id,
            'name' => 'Zero Range Profile',
            'gold_gain_range' => '0',
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertDatabaseHas('game_location_gem_paramters', [
            'name' => 'Zero Range Profile',
            'gold_gain_range' => null,
        ]);
    }

    public function test_store_normalizes_zero_to_zero_range_to_null(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $map = $this->createGameMap(['name' => 'Zero To Zero Range Map']);
        $location = $this->createLocation(['name' => 'Zero To Zero Range Location', 'game_map_id' => $map->id, 'type' => 1]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/location-gems', [
            'location_id' => $location->id,
            'name' => 'Zero To Zero Range Profile',
            'gold_gain_range' => '0-0',
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertDatabaseHas('game_location_gem_paramters', [
            'name' => 'Zero To Zero Range Profile',
            'gold_gain_range' => null,
        ]);
    }

    public function test_store_preserves_valid_configured_range(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $map = $this->createGameMap(['name' => 'Configured Range Map']);
        $location = $this->createLocation(['name' => 'Configured Range Location', 'game_map_id' => $map->id, 'type' => 1]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/location-gems', [
            'location_id' => $location->id,
            'name' => 'Configured Range Profile',
            'gold_gain_range' => '0.01-0.05',
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertDatabaseHas('game_location_gem_paramters', [
            'name' => 'Configured Range Profile',
            'gold_gain_range' => '0.01-0.05',
        ]);
    }

    public function test_edit_returns_current_form_values(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameLocationGemParamter(['name' => 'Edit Target Profile']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/location-gems/'.$profile->id.'/edit');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Edit Target Profile', $data['name']);
    }
}
