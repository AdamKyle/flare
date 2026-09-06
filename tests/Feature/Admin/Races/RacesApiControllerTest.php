<?php

namespace Tests\Feature\Admin\Races;

use App\Admin\Races\Imports\RacesImport;
use App\Flare\Models\GameRace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateRace;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class RacesApiControllerTest extends TestCase
{
    use CreateRace, CreateRole, CreateUser, RefreshDatabase;

    public function test_import_invokes_the_races_workbook_boundary(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $file = UploadedFile::fake()->create('races.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        Excel::shouldReceive('import')->once()->with(Mockery::type(RacesImport::class), $file);

        $response = $this->actingAs($admin)->post('/api/admin/races/import', ['races_import' => $file]);

        $response->assertOk()->assertJson(['message' => 'Races imported successfully.']);
    }

    public function test_index_rejects_unauthenticated_request(): void
    {
        $response = $this->call('GET', '/api/admin/races', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_index_rejects_non_admin_request(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/races', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_index_search_filters_by_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createRace(['name' => 'Elf']);
        $this->createRace(['name' => 'Dwarf']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/races?search_text=El');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertContains('Elf', $names);
        $this->assertNotContains('Dwarf', $names);
    }

    public function test_sorting_by_name_orders_results(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createRace(['name' => 'Zeta Race']);
        $this->createRace(['name' => 'Alpha Race']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/races?sort_key=name&sort_direction=asc');
        $data = json_decode($response->getContent(), true);
        $names = array_column($data['data'], 'name');

        $this->assertSame('Alpha Race', $names[0]);
    }

    public function test_update_persists_changed_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $race = $this->createRace(['name' => 'Old Race Name']);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/races/'.$race->id, [
            '_method' => 'PUT',
            'name' => 'New Race Name',
        ]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('New Race Name', $data['name']);
        $this->assertDatabaseHas('game_races', ['id' => $race->id, 'name' => 'New Race Name']);
    }

    public function test_update_persists_changed_description(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $race = $this->createRace(['name' => 'Description Update Race', 'description' => 'Old description.']);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/races/'.$race->id, [
            '_method' => 'PUT',
            'name' => 'Description Update Race',
            'description' => 'New description.',
        ]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('New description.', $data['description']);
        $this->assertDatabaseHas('game_races', ['id' => $race->id, 'description' => 'New description.']);
    }

    public function test_api_does_not_expose_old_race_modifiers(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $race = $this->createRace(['name' => 'Modifier Free Race']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/races/'.$race->id);
        $data = json_decode($response->getContent(), true);

        $this->assertArrayNotHasKey('str_mod', $data);
        $this->assertArrayNotHasKey('accuracy_mod', $data);
    }

    public function test_fallback_image_url_is_returned_when_there_is_no_race_image(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $race = $this->createRace(['name' => 'No Image Race']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/races/'.$race->id);
        $data = json_decode($response->getContent(), true);

        $this->assertStringContainsString('knight-in-a-field.png', $data['image_url']);
    }

    public function test_create_without_image_is_allowed(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('POST', '/api/admin/races', [
            'name' => 'Imageless Race',
            'description' => 'No image provided.',
        ]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertDatabaseHas('game_races', ['name' => 'Imageless Race', 'image_path' => null]);
    }

    public function test_image_upload_stores_relative_path(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin($this->createAdminRole());
        $image = UploadedFile::fake()->image('race.png', 10, 10);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/races', [
            'name' => 'Imaged Race',
        ], [], ['image' => $image]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(201, $response->getStatusCode());
        $storedPath = GameRace::find($data['id'])->image_path;
        $this->assertNotNull($storedPath);
        $this->assertStringStartsWith('race-images/', $storedPath);
        Storage::disk('public')->assertExists($storedPath);
    }

    public function test_replacement_image_updates_path(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin($this->createAdminRole());
        $race = $this->createRace(['name' => 'Replace Image Race', 'image_path' => 'race-images/original.png']);
        $image = UploadedFile::fake()->image('replacement.png', 10, 10);

        $response = $this->actingAs($admin)->call('POST', '/api/admin/races/'.$race->id, [
            '_method' => 'PUT',
            'name' => 'Replace Image Race',
        ], [], ['image' => $image]);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $race->refresh();
        $this->assertNotSame('race-images/original.png', $race->image_path);
        Storage::disk('public')->assertExists($race->image_path);
    }

    public function test_edit_returns_current_form_values(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $race = $this->createRace(['name' => 'Edit Target Race']);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/races/'.$race->id.'/edit');
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Edit Target Race', $data['name']);
    }
}
