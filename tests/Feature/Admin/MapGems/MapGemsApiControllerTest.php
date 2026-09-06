<?php

namespace Tests\Feature\Admin\MapGems;

use App\Admin\MapGems\Imports\MapGemsImport;
use App\Flare\Models\Gem;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use App\Game\Monsters\Values\MonsterCacheKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateGameMapGemParamter;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateGem;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class MapGemsApiControllerTest extends TestCase
{
    use CreateGameMap, CreateGameMapGemParamter, CreateGameSkill, CreateGem, CreateRole, CreateUser, RefreshDatabase;

    public function test_import_invokes_the_map_gems_workbook_boundary(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $file = UploadedFile::fake()->create('map-gems.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        Excel::shouldReceive('import')->once()->with(Mockery::type(MapGemsImport::class), $file);

        $response = $this->actingAs($admin)->post('/api/admin/map-gems/import', ['map_gems_import' => $file]);

        $response->assertOk()->assertJson(['message' => 'Map Gems imported successfully.']);
    }

    public function test_index_rejects_unauthenticated_request(): void
    {
        $response = $this->call('GET', '/api/admin/map-gems', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_index_rejects_non_admin_request(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/map-gems', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_map_filter_only_returns_profiles_for_that_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $mapA = $this->createGameMap(['name' => 'Map A']);
        $mapB = $this->createGameMap(['name' => 'Map B']);
        $this->createGameMapGemParamter(['name' => 'Profile A', 'game_map_id' => $mapA->id]);
        $this->createGameMapGemParamter(['name' => 'Profile B', 'game_map_id' => $mapB->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/map-gems?filters[game_map_id]='.$mapA->id);
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertContains('Profile A', $names);
        $this->assertNotContains('Profile B', $names);
    }

    public function test_sorting_by_name_orders_results(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameMapGemParamter(['name' => 'Zeta Profile']);
        $this->createGameMapGemParamter(['name' => 'Alpha Profile']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/map-gems?sort_key=name&sort_direction=asc');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertSame('Alpha Profile', $names[0]);
    }

    public function test_sorting_by_roll_count_orders_results(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameMapGemParamter(['name' => 'Low Roll Profile', 'roll_count' => 1]);
        $this->createGameMapGemParamter(['name' => 'High Roll Profile', 'roll_count' => 5]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/map-gems?sort_key=roll_count&sort_direction=desc');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertSame('High Roll Profile', $names[0]);
    }

    public function test_update_persists_changed_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'Old Profile Name']);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/map-gems/'.$profile->id, [
            'game_map_id' => $profile->game_map_id,
            'name' => 'New Profile Name',
        ]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('New Profile Name', $data['name']);
        $this->assertDatabaseHas('game_map_gem_paramters', ['id' => $profile->id, 'name' => 'New Profile Name']);
    }

    public function test_store_rejects_generated_source_game_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $generatedMap = $this->createGameMap(['name' => 'Generated Source Map', 'generated_map_type' => 'gem-world']);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/map-gems', [
            'game_map_id' => $generatedMap->id,
            'name' => 'Rejected Generated Profile',
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_update_rejects_generated_source_game_map(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'Update Target Profile']);
        $generatedMap = $this->createGameMap(['name' => 'Update Generated Map', 'generated_map_type' => 'gem-world']);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/map-gems/'.$profile->id, [
            'game_map_id' => $generatedMap->id,
            'name' => 'Update Target Profile',
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_store_rejects_trainable_crafting_skill(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $map = $this->createGameMap(['name' => 'Trainable Skill Map']);
        $skill = $this->createGameSkill(['name' => 'Trainable Skill', 'can_train' => true]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/map-gems', [
            'game_map_id' => $map->id,
            'name' => 'Trainable Skill Profile',
            'crafting_skill_ids' => [$skill->id],
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_update_rejects_trainable_crafting_skill(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'Update Skill Profile']);
        $skill = $this->createGameSkill(['name' => 'Update Trainable Skill', 'can_train' => true]);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/map-gems/'.$profile->id, [
            'game_map_id' => $profile->game_map_id,
            'name' => 'Update Skill Profile',
            'crafting_skill_ids' => [$skill->id],
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_search_filters_by_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameMapGemParamter(['name' => 'Findable Profile']);
        $this->createGameMapGemParamter(['name' => 'Other Profile']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/map-gems?search_text=Findable');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertContains('Findable Profile', $names);
        $this->assertNotContains('Other Profile', $names);
    }

    public function test_show_returns_profile_detail_with_character_power_reduction(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'Detail Profile']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/map-gems/'.$profile->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('character_power_reduction_range', $data['ranges']);
    }

    public function test_options_excludes_generated_child_maps(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $normalMap = $this->createGameMap(['name' => 'Normal Map']);
        $this->createGameMap(['name' => 'Generated Gem World', 'generated_map_type' => 'gem-world']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/map-gems/options');
        $data = json_decode($response->getContent(), true);
        $mapNames = array_column($data['game_maps'], 'name');

        $this->assertContains($normalMap->name, $mapNames);
        $this->assertNotContains('Generated Gem World', $mapNames);
    }

    public function test_generated_map_association_present_in_detail_when_relationship_exists(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'World Profile']);
        $generatedMap = $this->createGameMap([
            'name' => 'Generated World',
            'generated_map_type' => 'gem-world',
            'game_map_gem_paramter_id' => $profile->id,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/map-gems/'.$profile->id);
        $data = json_decode($response->getContent(), true);

        $this->assertNotNull($data['generated_gem_world']);
        $this->assertSame($generatedMap->id, $data['generated_gem_world']['id']);
        $this->assertSame('Generated World', $data['generated_gem_world']['name']);
    }

    public function test_generated_map_association_includes_parent_map_id_when_present(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'World Parent Profile']);
        $parentMap = $this->createGameMap(['name' => 'Parent Surface Map']);
        $this->createGameMap([
            'name' => 'Generated World With Parent',
            'generated_map_type' => 'gem-world',
            'game_map_gem_paramter_id' => $profile->id,
            'generated_parent_game_map_id' => $parentMap->id,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/map-gems/'.$profile->id);
        $data = json_decode($response->getContent(), true);

        $this->assertNotNull($data['generated_gem_world']['parent_map']);
        $this->assertSame($parentMap->id, $data['generated_gem_world']['parent_map']['id']);
    }

    public function test_roll_endpoint_calls_existing_roll_behavior_and_returns_updated_detail(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'Rollable Profile', 'roll_count' => 0]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/map-gems/'.$profile->id.'/roll');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotNull($data['rolled_gem']);
        $this->assertSame(1, $data['roll_count']);
        $this->assertDatabaseHas('gems', ['name' => $profile->name, 'roll_number' => 1]);
    }

    public function test_single_roll_invalidates_gem_affected_monster_caches(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'Cache Invalidation Profile']);

        resolve(BuildMonsterCacheService::class)->buildAll();

        $this->actingAs($admin)->call('POST', '/api/admin/map-gems/'.$profile->id.'/roll');

        $this->assertFalse(Cache::has(MonsterCacheKey::MONSTERS->value));
        $this->assertFalse(Cache::has(MonsterCacheKey::LOCATION_MONSTERS->value));
        $this->assertTrue(Cache::has(MonsterCacheKey::RAID_MONSTERS->value));
    }

    public function test_roll_all_rolls_a_profile_without_a_rolled_gem(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'Unrolled Profile', 'roll_count' => 0]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/map-gems/roll-all');
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
        $profile = $this->createGameMapGemParamter(['name' => 'Already Rolled Profile']);
        $existingGem = $this->createMapGeneratedGem($profile, ['name' => 'Existing Gem', 'roll_number' => 3]);
        $profile->update(['rolled_gem_id' => $existingGem->id, 'roll_count' => 3]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/map-gems/roll-all');
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
        $zeroRangeProfile = $this->createGameMapGemParamter([
            'name' => 'Zero Range Profile',
            'roll_count' => 0,
            'character_power_reduction_range' => '0',
            'gold_gain_range' => '0.4321-0.4321',
        ]);
        $normalProfile = $this->createGameMapGemParamter(['name' => 'Normal Profile', 'roll_count' => 0]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/map-gems/roll-all');
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
        $this->assertNull($zeroRangeRow['rolled_gem']['character_power_reduction']);
        $this->assertSame(0.4321, $zeroRangeRow['rolled_gem']['gold_gain']);
    }

    public function test_roll_all_invalidates_gem_affected_monster_caches_when_rerolling(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'Reroll Cache Profile']);
        $existingGem = $this->createMapGeneratedGem($profile, ['name' => 'Reroll Cache Gem']);
        $profile->update(['rolled_gem_id' => $existingGem->id, 'roll_count' => 1]);

        resolve(BuildMonsterCacheService::class)->buildAll();

        $this->actingAs($admin)->call('POST', '/api/admin/map-gems/roll-all');

        $this->assertFalse(Cache::has(MonsterCacheKey::MONSTERS->value));
        $this->assertFalse(Cache::has(MonsterCacheKey::LOCATION_MONSTERS->value));
    }

    public function test_roll_all_succeeds_when_a_generated_gem_world_already_exists(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'World Backed Profile']);
        $existingGem = $this->createMapGeneratedGem($profile, ['name' => 'World Backed Gem']);
        $profile->update(['rolled_gem_id' => $existingGem->id, 'roll_count' => 1]);
        $this->createGameMap([
            'name' => 'Roll All World',
            'generated_map_type' => 'gem-world',
            'game_map_gem_paramter_id' => $profile->id,
        ]);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/map-gems/roll-all');
        $data = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertSame(1, $data['rolled_count']);
        $this->assertSame(2, $profile->refresh()->roll_count);
        $this->assertNotSame($existingGem->id, $profile->rolled_gem_id);
    }

    public function test_detail_roll_history_includes_every_roll_newest_first_with_active_marked(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'History Profile']);
        $firstGem = $this->createMapGeneratedGem($profile, ['name' => 'History Gem 1', 'roll_number' => 1]);
        $secondGem = $this->createMapGeneratedGem($profile, ['name' => 'History Gem 2', 'roll_number' => 2]);
        $profile->update(['rolled_gem_id' => $secondGem->id, 'roll_count' => 2]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/map-gems/'.$profile->id);
        $data = json_decode($response->getContent(), true);

        $this->assertCount(2, $data['roll_history']);
        $this->assertSame($secondGem->id, $data['roll_history'][0]['id']);
        $this->assertTrue($data['roll_history'][0]['is_active']);
        $this->assertSame($firstGem->id, $data['roll_history'][1]['id']);
        $this->assertFalse($data['roll_history'][1]['is_active']);
        $this->assertTrue($data['rolled_gem']['is_active']);
        $this->assertSame($secondGem->id, $data['rolled_gem']['id']);
    }

    public function test_activate_roll_switches_active_gem_without_changing_roll_count(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'Activation Profile']);
        $firstGem = $this->createMapGeneratedGem($profile, ['name' => 'Activation Gem 1', 'roll_number' => 1]);
        $secondGem = $this->createMapGeneratedGem($profile, ['name' => 'Activation Gem 2', 'roll_number' => 2]);
        $profile->update(['rolled_gem_id' => $secondGem->id, 'roll_count' => 2]);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/map-gems/'.$profile->id.'/rolls/'.$firstGem->id.'/activate');
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
        $profile = $this->createGameMapGemParamter(['name' => 'Activation Cache Profile']);
        $firstGem = $this->createMapGeneratedGem($profile, ['name' => 'Activation Cache Gem 1', 'roll_number' => 1]);
        $secondGem = $this->createMapGeneratedGem($profile, ['name' => 'Activation Cache Gem 2', 'roll_number' => 2]);
        $profile->update(['rolled_gem_id' => $secondGem->id, 'roll_count' => 2]);

        resolve(BuildMonsterCacheService::class)->buildAll();

        $this->actingAs($admin)->call('PUT', '/api/admin/map-gems/'.$profile->id.'/rolls/'.$firstGem->id.'/activate');

        $this->assertFalse(Cache::has(MonsterCacheKey::MONSTERS->value));
        $this->assertFalse(Cache::has(MonsterCacheKey::LOCATION_MONSTERS->value));
    }

    public function test_activate_roll_succeeds_when_a_generated_gem_world_exists(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'Activation World Profile']);
        $firstGem = $this->createMapGeneratedGem($profile, ['name' => 'Activation World Gem 1', 'roll_number' => 1]);
        $secondGem = $this->createMapGeneratedGem($profile, ['name' => 'Activation World Gem 2', 'roll_number' => 2]);
        $profile->update(['rolled_gem_id' => $secondGem->id, 'roll_count' => 2]);
        $this->createGameMap([
            'name' => 'Activation World',
            'generated_map_type' => 'gem-world',
            'game_map_gem_paramter_id' => $profile->id,
        ]);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/map-gems/'.$profile->id.'/rolls/'.$firstGem->id.'/activate');

        $response->assertStatus(200);
        $this->assertSame($firstGem->id, $profile->refresh()->rolled_gem_id);
    }

    public function test_activate_roll_rejects_gem_from_another_profile(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'Owner Profile']);
        $otherProfile = $this->createGameMapGemParamter(['name' => 'Other Profile']);
        $activeGem = $this->createMapGeneratedGem($profile, ['name' => 'Owner Gem', 'roll_number' => 1]);
        $profile->update(['rolled_gem_id' => $activeGem->id, 'roll_count' => 1]);
        $foreignGem = $this->createMapGeneratedGem($otherProfile, ['name' => 'Foreign Gem', 'roll_number' => 1]);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/map-gems/'.$profile->id.'/rolls/'.$foreignGem->id.'/activate');

        $response->assertStatus(422);
        $this->assertSame($activeGem->id, $profile->refresh()->rolled_gem_id);
    }

    public function test_activate_roll_rejects_gem_from_wrong_domain(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'Wrong Domain Profile']);
        $activeGem = $this->createMapGeneratedGem($profile, ['name' => 'Wrong Domain Active Gem', 'roll_number' => 1]);
        $profile->update(['rolled_gem_id' => $activeGem->id, 'roll_count' => 1]);
        $locationGem = $this->createGem(['name' => 'Wrong Domain Location Gem', 'domain' => 'location']);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/map-gems/'.$profile->id.'/rolls/'.$locationGem->id.'/activate');

        $response->assertStatus(422);
        $this->assertSame($activeGem->id, $profile->refresh()->rolled_gem_id);
    }

    public function test_store_persists_valid_map_gem_profile(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $map = $this->createGameMap(['name' => 'Store Map']);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/map-gems', [
            'game_map_id' => $map->id,
            'name' => 'Created Profile',
            'description' => 'A profile.',
            'character_xp_bonus_range' => '0.01-0.05',
        ]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Created Profile', $data['name']);
        $this->assertDatabaseHas('game_map_gem_paramters', ['name' => 'Created Profile']);
    }

    public function test_store_rejects_invalid_range_format(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $map = $this->createGameMap(['name' => 'Invalid Range Map']);

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/map-gems',
            [
                'game_map_id' => $map->id,
                'name' => 'Invalid Range Profile',
                'character_xp_bonus_range' => 'not-a-range',
            ],
            [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_edit_returns_current_form_values(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $profile = $this->createGameMapGemParamter(['name' => 'Edit Target Profile']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/map-gems/'.$profile->id.'/edit');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Edit Target Profile', $data['name']);
    }
}
