<?php

namespace Tests\Feature\Admin\Classes;

use App\Admin\Classes\Imports\ClassesImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class ClassesApiControllerTest extends TestCase
{
    use CreateClass, CreateRole, CreateUser, RefreshDatabase;

    public function test_import_invokes_the_classes_workbook_boundary(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $file = UploadedFile::fake()->create('classes.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        Excel::shouldReceive('import')->once()->with(Mockery::type(ClassesImport::class), $file);

        $response = $this->actingAs($admin)->post('/api/admin/classes/import', ['classes_import' => $file]);

        $response->assertOk()->assertJson(['message' => 'Classes imported successfully.']);
    }

    public function test_index_rejects_unauthenticated_request(): void
    {
        $response = $this->call('GET', '/api/admin/classes', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_index_rejects_non_admin_request(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/classes', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_index_search_filters_by_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createClass(['name' => 'Fighter']);
        $this->createClass(['name' => 'Ranger']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/classes?search_text=Fight');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertContains('Fighter', $names);
        $this->assertNotContains('Ranger', $names);
    }

    public function test_index_sort_key_orders_results(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createClass(['name' => 'Zeta Class']);
        $this->createClass(['name' => 'Alpha Class']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/classes?sort_key=name&sort_direction=asc');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertSame('Alpha Class', $names[0]);
    }

    public function test_show_returns_class_detail(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Show Class', 'description' => 'Details here']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/classes/'.$gameClass->id);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Show Class', $data['name']);
        $this->assertSame('Details here', $data['description']);
    }

    public function test_show_omits_unlock_requirements_for_normal_class(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Normal Class']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/classes/'.$gameClass->id);
        $data = json_decode($response->getContent(), true);

        $this->assertNull($data['unlock_requirements']);
    }

    public function test_edit_returns_current_form_values(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Edit Target']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/classes/'.$gameClass->id.'/edit');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Edit Target', $data['name']);
    }

    public function test_store_persists_normal_class_with_no_unlock_requirements(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('POST', '/api/admin/classes', [
            'name' => 'Created Class', 'description' => 'A test class.', 'damage_stat' => 'str',
            'to_hit_stat' => 'dex', 'str_mod' => 1, 'dur_mod' => 0, 'dex_mod' => 0,
            'chr_mod' => 0, 'int_mod' => 0, 'agi_mod' => 0, 'focus_mod' => 0,
            'accuracy_mod' => 0.01, 'dodge_mod' => 0.01, 'defense_mod' => 0.01, 'looting_mod' => 0.01,
            'primary_required_class_id' => null, 'secondary_required_class_id' => null,
            'primary_required_class_level' => null, 'secondary_required_class_level' => null,
        ]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Created Class', $data['name']);
        $this->assertDatabaseHas('game_classes', ['name' => 'Created Class']);
    }

    public function test_store_persists_special_class_with_complete_unlock_requirements(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $primary = $this->createClass(['name' => 'Primary Prereq']);
        $secondary = $this->createClass(['name' => 'Secondary Prereq']);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/classes', [
            'name' => 'Special Class',
            'description' => 'A test class.', 'damage_stat' => 'str', 'to_hit_stat' => 'dex',
            'str_mod' => 1, 'dur_mod' => 0, 'dex_mod' => 0, 'chr_mod' => 0,
            'int_mod' => 0, 'agi_mod' => 0, 'focus_mod' => 0, 'accuracy_mod' => 0.01,
            'dodge_mod' => 0.01, 'defense_mod' => 0.01, 'looting_mod' => 0.01,
            'primary_required_class_id' => $primary->id,
            'secondary_required_class_id' => $secondary->id,
            'primary_required_class_level' => 10,
            'secondary_required_class_level' => 20,
        ]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertDatabaseHas('game_classes', [
            'name' => 'Special Class',
            'primary_required_class_id' => $primary->id,
            'secondary_required_class_id' => $secondary->id,
        ]);
    }

    public function test_store_rejects_incomplete_unlock_requirements(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $primary = $this->createClass(['name' => 'Only Primary']);

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/classes',
            [
                'name' => 'Half Configured Class',
                'description' => 'A test class.', 'damage_stat' => 'str', 'to_hit_stat' => 'dex',
                'str_mod' => 1, 'dur_mod' => 0, 'dex_mod' => 0, 'chr_mod' => 0,
                'int_mod' => 0, 'agi_mod' => 0, 'focus_mod' => 0, 'accuracy_mod' => 0.01,
                'dodge_mod' => 0.01, 'defense_mod' => 0.01, 'looting_mod' => 0.01,
                'primary_required_class_id' => $primary->id,
                'secondary_required_class_id' => null, 'primary_required_class_level' => null,
                'secondary_required_class_level' => null,
            ],
            [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_store_rejects_same_prerequisite_used_twice(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $prereq = $this->createClass(['name' => 'Duplicate Prereq']);

        $response = $this->actingAs($admin)->call(
            'POST',
            '/api/admin/classes',
            [
                'name' => 'Duplicate Requirement Class',
                'description' => 'A test class.', 'damage_stat' => 'str', 'to_hit_stat' => 'dex',
                'str_mod' => 1, 'dur_mod' => 0, 'dex_mod' => 0, 'chr_mod' => 0,
                'int_mod' => 0, 'agi_mod' => 0, 'focus_mod' => 0, 'accuracy_mod' => 0.01,
                'dodge_mod' => 0.01, 'defense_mod' => 0.01, 'looting_mod' => 0.01,
                'primary_required_class_id' => $prereq->id,
                'secondary_required_class_id' => $prereq->id,
                'primary_required_class_level' => 10,
                'secondary_required_class_level' => 20,
            ],
            [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_update_rejects_class_requiring_itself(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Self Class']);
        $other = $this->createClass(['name' => 'Other Class']);

        $response = $this->actingAs($admin)->call(
            'PUT',
            '/api/admin/classes/'.$gameClass->id,
            [
                'name' => 'Self Class',
                'description' => 'A test class.', 'damage_stat' => 'str', 'to_hit_stat' => 'dex',
                'str_mod' => 1, 'dur_mod' => 0, 'dex_mod' => 0, 'chr_mod' => 0,
                'int_mod' => 0, 'agi_mod' => 0, 'focus_mod' => 0, 'accuracy_mod' => 0.01,
                'dodge_mod' => 0.01, 'defense_mod' => 0.01, 'looting_mod' => 0.01,
                'primary_required_class_id' => $gameClass->id,
                'secondary_required_class_id' => $other->id,
                'primary_required_class_level' => 10,
                'secondary_required_class_level' => 20,
            ],
            [], [], ['HTTP_ACCEPT' => 'application/json'],
        );

        $this->assertSame(422, $response->getStatusCode());
    }

    public function test_description_persists_in_api_response(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('POST', '/api/admin/classes', [
            'name' => 'Described Class',
            'description' => 'This class has a description.',
            'damage_stat' => 'str', 'to_hit_stat' => 'dex', 'str_mod' => 1, 'dur_mod' => 0,
            'dex_mod' => 0, 'chr_mod' => 0, 'int_mod' => 0, 'agi_mod' => 0, 'focus_mod' => 0,
            'accuracy_mod' => 0.01, 'dodge_mod' => 0.01, 'defense_mod' => 0.01, 'looting_mod' => 0.01,
            'primary_required_class_id' => null, 'secondary_required_class_id' => null,
            'primary_required_class_level' => null, 'secondary_required_class_level' => null,
        ]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame('This class has a description.', $data['description']);
    }

    public function test_update_modifies_the_class(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Original Name']);

        $response = $this->actingAs($admin)->call('PUT', '/api/admin/classes/'.$gameClass->id, [
            'name' => 'Updated Name',
            'description' => 'A test class.', 'damage_stat' => 'str', 'to_hit_stat' => 'dex',
            'str_mod' => 1, 'dur_mod' => 0, 'dex_mod' => 0, 'chr_mod' => 0,
            'int_mod' => 0, 'agi_mod' => 0, 'focus_mod' => 0, 'accuracy_mod' => 0.01,
            'dodge_mod' => 0.01, 'defense_mod' => 0.01, 'looting_mod' => 0.01,
            'primary_required_class_id' => null, 'secondary_required_class_id' => null,
            'primary_required_class_level' => null, 'secondary_required_class_level' => null,
        ]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Updated Name', $data['name']);
    }

    public function test_options_returns_stat_and_class_options(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createClass(['name' => 'Option Class']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/classes/options');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertContains('str', $data['stats']);
        $this->assertNotEmpty($data['classes']);
    }
}
