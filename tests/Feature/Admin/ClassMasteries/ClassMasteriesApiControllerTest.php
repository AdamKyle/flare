<?php

namespace Tests\Feature\Admin\ClassMasteries;

use App\Admin\ClassMasteries\Imports\ClassMasteriesImport;
use App\Game\ClassRanks\Values\ClassRankValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameClassSpecial;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class ClassMasteriesApiControllerTest extends TestCase
{
    use CreateClass, CreateGameClassSpecial, CreateRole, CreateUser, RefreshDatabase;

    public function test_import_invokes_the_class_masteries_workbook_boundary(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $file = UploadedFile::fake()->create('class-masteries.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        Excel::shouldReceive('import')->once()->with(Mockery::type(ClassMasteriesImport::class), $file);

        $response = $this->actingAs($admin)->post('/api/admin/class-masteries/import', ['class_masteries_import' => $file]);

        $response->assertOk()->assertJson(['message' => 'Class Masteries imported successfully.']);
    }

    public function test_index_rejects_unauthenticated_request(): void
    {
        $response = $this->call('GET', '/api/admin/class-masteries', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_index_rejects_non_admin_request(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/class-masteries', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_class_filter_only_returns_masteries_for_that_class(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $classA = $this->createClass(['name' => 'Class A']);
        $classB = $this->createClass(['name' => 'Class B']);
        $this->createGameClassSpecial(['name' => 'Mastery A', 'game_class_id' => $classA->id]);
        $this->createGameClassSpecial(['name' => 'Mastery B', 'game_class_id' => $classB->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/class-masteries?filters[game_class_id]='.$classA->id);
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertContains('Mastery A', $names);
        $this->assertNotContains('Mastery B', $names);
    }

    public function test_search_filters_by_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Search Class']);
        $this->createGameClassSpecial(['name' => 'Findable Mastery', 'game_class_id' => $gameClass->id]);
        $this->createGameClassSpecial(['name' => 'Other Mastery', 'game_class_id' => $gameClass->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/class-masteries?search_text=Findable');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertContains('Findable Mastery', $names);
        $this->assertNotContains('Other Mastery', $names);
    }

    public function test_search_filters_by_description(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Search Description Class']);
        $this->createGameClassSpecial([
            'name' => 'Mastery One',
            'description' => 'Grants a unique searchable bonus.',
            'game_class_id' => $gameClass->id,
        ]);
        $this->createGameClassSpecial([
            'name' => 'Mastery Two',
            'description' => 'Unrelated text.',
            'game_class_id' => $gameClass->id,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/class-masteries?search_text=searchable');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertContains('Mastery One', $names);
        $this->assertNotContains('Mastery Two', $names);
    }

    public function test_sorting_by_requires_class_rank_level_orders_results(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Sorting Class']);
        $this->createGameClassSpecial(['name' => 'High Rank Mastery', 'game_class_id' => $gameClass->id, 'requires_class_rank_level' => 20]);
        $this->createGameClassSpecial(['name' => 'Low Rank Mastery', 'game_class_id' => $gameClass->id, 'requires_class_rank_level' => 5]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/class-masteries?sort_key=requires_class_rank_level&sort_direction=asc');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertSame('Low Rank Mastery', $names[0]);
    }

    public function test_show_returns_factual_class_mastery_fields(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Factual Class']);
        $mastery = $this->createGameClassSpecial([
            'name' => 'Factual Mastery',
            'description' => 'Factual description.',
            'game_class_id' => $gameClass->id,
            'requires_class_rank_level' => 12,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/class-masteries/'.$mastery->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame('Factual Mastery', $data['name']);
        $this->assertSame('Factual description.', $data['description']);
        $this->assertSame($gameClass->id, $data['game_class']['id']);
        $this->assertSame(12, $data['requires_class_rank_level']);
    }

    public function test_store_requires_description(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Missing Description Class']);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/class-masteries', [
            'game_class_id' => $gameClass->id,
            'name' => 'No Description Mastery',
            'requires_class_rank_level' => 5,
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('description');
    }

    public function test_store_rejects_class_rank_below_minimum(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Below Minimum Class']);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/class-masteries', [
            'game_class_id' => $gameClass->id,
            'name' => 'Below Minimum Mastery',
            'description' => 'A mastery.',
            'requires_class_rank_level' => -1,
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('requires_class_rank_level');
    }

    public function test_store_rejects_class_rank_above_maximum(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Above Maximum Class']);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/class-masteries', [
            'game_class_id' => $gameClass->id,
            'name' => 'Above Maximum Mastery',
            'description' => 'A mastery.',
            'requires_class_rank_level' => ClassRankValue::MAX_LEVEL + 1,
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('requires_class_rank_level');
    }

    public function test_store_rejects_invalid_attack_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Invalid Attack Type Class']);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/class-masteries', [
            'game_class_id' => $gameClass->id,
            'name' => 'Invalid Attack Type Mastery',
            'description' => 'A mastery.',
            'requires_class_rank_level' => 5,
            'attack_type_required' => 'not-a-real-attack-type',
        ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('attack_type_required');
    }

    public function test_derived_type_is_attack_for_positive_specialty_damage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Attack Class']);
        $mastery = $this->createGameClassSpecial([
            'name' => 'Attack Mastery',
            'game_class_id' => $gameClass->id,
            'specialty_damage' => 50,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/class-masteries/'.$mastery->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame('attack', $data['type']);
    }

    public function test_derived_type_is_passive_for_null_specialty_damage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Passive Class']);
        $mastery = $this->createGameClassSpecial([
            'name' => 'Passive Mastery',
            'game_class_id' => $gameClass->id,
            'specialty_damage' => null,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/class-masteries/'.$mastery->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame('passive', $data['type']);
    }

    public function test_response_contains_no_character_equipped_progression_data(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Clean Class']);
        $mastery = $this->createGameClassSpecial(['name' => 'Clean Mastery', 'game_class_id' => $gameClass->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/class-masteries/'.$mastery->id);
        $data = json_decode($response->getContent(), true);

        $this->assertArrayNotHasKey('characters', $data);
        $this->assertArrayNotHasKey('equipped', $data);
        $this->assertArrayNotHasKey('xp', $data);
        $this->assertArrayNotHasKey('level', $data);
    }

    public function test_store_persists_valid_class_mastery(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'New Mastery Class']);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/class-masteries', [
            'game_class_id' => $gameClass->id,
            'name' => 'Created Mastery',
            'description' => 'A mastery.',
            'requires_class_rank_level' => 5,
        ]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Created Mastery', $data['name']);
        $this->assertDatabaseHas('game_class_specials', ['name' => 'Created Mastery']);
    }

    public function test_update_modifies_the_class_mastery(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Update Mastery Class']);
        $mastery = $this->createGameClassSpecial(['name' => 'Original Mastery', 'game_class_id' => $gameClass->id]);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/class-masteries/'.$mastery->id, [
            'game_class_id' => $gameClass->id,
            'name' => 'Updated Mastery',
            'description' => 'Updated mastery description.',
            'requires_class_rank_level' => 10,
        ]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Updated Mastery', $data['name']);
    }

    public function test_edit_returns_current_form_values(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Edit Mastery Class']);
        $mastery = $this->createGameClassSpecial(['name' => 'Edit Target Mastery', 'game_class_id' => $gameClass->id]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/class-masteries/'.$mastery->id.'/edit');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Edit Target Mastery', $data['name']);
    }

    public function test_options_returns_classes_and_attack_types(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createClass(['name' => 'Option Class']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/class-masteries/options');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotEmpty($data['classes']);
        $this->assertContains('any', $data['attack_types']);
    }
}
