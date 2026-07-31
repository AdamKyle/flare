<?php

namespace Tests\Feature\Admin\LocationTemplates;

use App\Admin\Exports\LocationTemplates\Sheets\LocationTemplatesSheet as ExportLocationTemplatesSheet;
use App\Admin\Import\LocationTemplates\Sheets\LocationTemplatesSheet as ImportLocationTemplatesSheet;
use App\Flare\Models\LocationTemplate;
use App\Flare\Values\LocationTemplateType;
use App\Flare\View\Livewire\Admin\LocationTemplates\LocationTemplatesTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Tests\TestCase;
use Tests\Traits\CreateLocationTemplate;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class LocationTemplateControllerTest extends TestCase
{
    use CreateLocationTemplate, CreateRole, CreateUser, RefreshDatabase;

    public function test_location_template_can_be_created_with_valid_fields(): void
    {
        $locationTemplate = LocationTemplate::create([
            'name' => 'Valid Template',
            'description' => 'Valid template description',
            'type' => LocationTemplateType::REGULAR->value,
        ]);

        $this->assertSame('Valid Template', $locationTemplate->name);
        $this->assertFalse($locationTemplate->is_port);
        $this->assertTrue($locationTemplate->can_players_enter);
    }

    public function test_request_validation_rejects_missing_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->post(route('admin.location-templates.store'), [
            'id' => 0,
            'description' => 'Missing name description',
            'type' => LocationTemplateType::REGULAR->value,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_request_validation_rejects_missing_description(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->post(route('admin.location-templates.store'), [
            'id' => 0,
            'name' => 'Missing Description',
            'type' => LocationTemplateType::REGULAR->value,
        ]);

        $response->assertSessionHasErrors('description');
    }

    public function test_request_validation_rejects_missing_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->post(route('admin.location-templates.store'), [
            'id' => 0,
            'name' => 'Missing Type',
            'description' => 'Missing type description',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_request_validation_rejects_duplicate_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $this->createLocationTemplate([
            'name' => 'Duplicate Template',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.location-templates.store'), [
            'id' => 0,
            'name' => 'Duplicate Template',
            'description' => 'Unique duplicate-name description',
            'type' => LocationTemplateType::REGULAR->value,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_request_validation_rejects_duplicate_description(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $this->createLocationTemplate([
            'description' => 'Duplicate description',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.location-templates.store'), [
            'id' => 0,
            'name' => 'Unique Duplicate Description',
            'description' => 'Duplicate description',
            'type' => LocationTemplateType::REGULAR->value,
        ]);

        $response->assertSessionHasErrors('description');
    }

    public function test_admin_can_create_location_template(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->post(route('admin.location-templates.store'), [
            'id' => 0,
            'name' => 'Created Template',
            'description' => 'Created template description',
            'type' => LocationTemplateType::REGULAR->value,
            'can_players_enter' => 1,
        ]);

        $locationTemplate = LocationTemplate::where('name', 'Created Template')->first();

        $this->assertNotNull($locationTemplate);
    }

    public function test_admin_can_create_special_location_template(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $this->actingAs($admin)->post(route('admin.location-templates.store'), [
            'id' => 0,
            'name' => 'Special Template',
            'description' => 'Special template description',
            'type' => LocationTemplateType::SPECIAL->value,
            'can_players_enter' => 1,
        ]);

        $locationTemplate = LocationTemplate::where('name', 'Special Template')->first();

        $this->assertNotNull($locationTemplate);
        $this->assertSame(LocationTemplateType::SPECIAL->value, $locationTemplate->type);
        $this->assertFalse($locationTemplate->is_port);
    }

    public function test_admin_can_update_location_template(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $locationTemplate = $this->createLocationTemplate();

        $this->actingAs($admin)->post(route('admin.location-templates.store'), [
            'id' => $locationTemplate->id,
            'name' => 'Updated Template',
            'description' => 'Updated template description',
            'type' => LocationTemplateType::DELVE->value,
            'can_players_enter' => 1,
        ]);

        $this->assertSame('Updated Template', $locationTemplate->refresh()->name);
        $this->assertSame(LocationTemplateType::DELVE->value, $locationTemplate->type);
    }

    public function test_admin_can_update_location_template_to_special(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $locationTemplate = $this->createLocationTemplate();

        $this->actingAs($admin)->post(route('admin.location-templates.store'), [
            'id' => $locationTemplate->id,
            'name' => 'Updated Special Template',
            'description' => 'Updated special template description',
            'type' => LocationTemplateType::SPECIAL->value,
            'can_players_enter' => 1,
        ]);

        $locationTemplate->refresh();

        $this->assertSame(LocationTemplateType::SPECIAL->value, $locationTemplate->type);
        $this->assertFalse($locationTemplate->is_port);
    }

    public function test_admin_can_view_location_template(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $locationTemplate = $this->createLocationTemplate([
            'name' => 'Viewable Template',
            'description' => 'Viewable template description',
            'type' => LocationTemplateType::REGULAR->value,
        ]);

        $this->actingAs($admin)
            ->visit(route('admin.location-templates.show', ['locationTemplate' => $locationTemplate]))
            ->see('Viewable Template')
            ->see('Viewable template description')
            ->see('Can Players Enter');
    }

    public function test_special_location_template_displays_on_show_page(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $locationTemplate = $this->createLocationTemplate([
            'name' => 'Show Special Template',
            'description' => 'Show special template description',
            'type' => LocationTemplateType::SPECIAL->value,
        ]);

        $this->actingAs($admin)
            ->visit(route('admin.location-templates.show', ['locationTemplate' => $locationTemplate]))
            ->see('Show Special Template')
            ->see('Special');
    }

    public function test_location_template_table_links_name_to_show_and_keeps_edit_action_only(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $locationTemplate = $this->createLocationTemplate([
            'name' => 'Linked Template',
        ]);

        $this->actingAs($admin);

        Livewire::test(LocationTemplatesTable::class)
            ->assertSee('Linked Template')
            ->assertSee(route('admin.location-templates.show', ['locationTemplate' => $locationTemplate]), false)
            ->assertSee(route('admin.location-templates.edit', ['locationTemplate' => $locationTemplate]), false)
            ->assertDontSee('>View</a>', false);
    }

    public function test_special_location_template_displays_in_livewire_table(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $locationTemplate = $this->createLocationTemplate([
            'name' => 'Table Special Template',
            'type' => LocationTemplateType::SPECIAL->value,
        ]);

        $this->actingAs($admin);

        Livewire::test(LocationTemplatesTable::class)
            ->assertSee('Table Special Template')
            ->assertSee(LocationTemplateType::SPECIAL->value)
            ->assertSee(route('admin.location-templates.show', ['locationTemplate' => $locationTemplate]), false);
    }

    public function test_location_templates_sidebar_has_icon(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $this->actingAs($admin)
            ->visitRoute('admin.location-templates.list')
            ->see('<span class="fas fa-map-marked-alt"></span>', false)
            ->see('Location Templates');
    }

    public function test_location_templates_table_has_type_filter(): void
    {
        $filters = (new LocationTemplatesTable)->filters();

        $this->assertInstanceOf(SelectFilter::class, $filters[0]);
        $this->assertSame('Type', $filters[0]->getName());
    }

    public function test_admin_can_delete_location_template(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $locationTemplate = $this->createLocationTemplate();

        $this->actingAs($admin)->post(route('admin.location-templates.delete', ['locationTemplate' => $locationTemplate]));

        $this->assertNull(LocationTemplate::find($locationTemplate->id));
    }

    public function test_export_has_expected_headings_and_shape(): void
    {
        $this->createLocationTemplate([
            'name' => 'Export Template',
            'description' => 'Export template description',
            'type' => LocationTemplateType::PORT->value,
        ]);

        $renderedView = (new ExportLocationTemplatesSheet)->view()->render();

        $this->assertStringContainsString('<th>name</th>', $renderedView);
        $this->assertStringContainsString('<th>description</th>', $renderedView);
        $this->assertStringContainsString('Export Template', $renderedView);
    }

    public function test_export_includes_special_location_templates(): void
    {
        $this->createLocationTemplate([
            'name' => 'Export Special Template',
            'description' => 'Export special template description',
            'type' => LocationTemplateType::SPECIAL->value,
        ]);

        $renderedView = (new ExportLocationTemplatesSheet)->view()->render();

        $this->assertStringContainsString('Export Special Template', $renderedView);
        $this->assertStringContainsString('<td>special</td>', $renderedView);
    }

    public function test_import_creates_templates(): void
    {
        (new ImportLocationTemplatesSheet)->collection(collect([
            collect(['name', 'description', 'type', 'can_players_enter']),
            collect(['Imported Template', 'Imported template description', LocationTemplateType::REGULAR->value, 1]),
        ]));

        $this->assertNotNull(LocationTemplate::where([
            'name' => 'Imported Template',
            'description' => 'Imported template description',
            'type' => LocationTemplateType::REGULAR->value,
        ])->first());
    }

    public function test_import_creates_special_templates(): void
    {
        (new ImportLocationTemplatesSheet)->collection(collect([
            collect(['name', 'description', 'type', 'is_port', 'can_players_enter']),
            collect(['Imported Special Template', 'Imported special template description', LocationTemplateType::SPECIAL->value, 0, 1]),
        ]));

        $locationTemplate = LocationTemplate::where('name', 'Imported Special Template')->first();

        $this->assertNotNull($locationTemplate);
        $this->assertSame(LocationTemplateType::SPECIAL->value, $locationTemplate->type);
        $this->assertFalse($locationTemplate->is_port);
    }

    public function test_request_validation_rejects_invalid_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->post(route('admin.location-templates.store'), [
            'id' => 0,
            'name' => 'Invalid Type Template',
            'description' => 'Invalid type template description',
            'type' => 'unknown',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_import_rejects_duplicate_names(): void
    {
        $this->createLocationTemplate([
            'name' => 'Imported Duplicate Name',
        ]);

        $this->expectException(ValidationException::class);

        (new ImportLocationTemplatesSheet)->collection(collect([
            collect(['name', 'description', 'type', 'can_players_enter']),
            collect(['Imported Duplicate Name', 'Imported duplicate name description', LocationTemplateType::REGULAR->value, 1]),
        ]));
    }

    public function test_import_rejects_duplicate_descriptions(): void
    {
        $this->createLocationTemplate([
            'description' => 'Imported duplicate description',
        ]);

        $this->expectException(ValidationException::class);

        (new ImportLocationTemplatesSheet)->collection(collect([
            collect(['name', 'description', 'type', 'can_players_enter']),
            collect(['Imported Duplicate Description', 'Imported duplicate description', LocationTemplateType::REGULAR->value, 1]),
        ]));
    }

    public function test_port_type_sets_is_port_true(): void
    {
        $locationTemplate = $this->createLocationTemplate([
            'type' => LocationTemplateType::PORT->value,
            'is_port' => false,
        ]);

        $this->assertTrue($locationTemplate->is_port);
    }

    public function test_regular_type_sets_is_port_false(): void
    {
        $locationTemplate = $this->createLocationTemplate([
            'type' => LocationTemplateType::REGULAR->value,
            'is_port' => true,
        ]);

        $this->assertFalse($locationTemplate->is_port);
    }

    public function test_delve_type_is_separate_from_regular_and_port_pool(): void
    {
        $locationTemplate = $this->createLocationTemplate([
            'type' => LocationTemplateType::DELVE->value,
        ]);

        $this->assertSame(LocationTemplateType::DELVE->value, $locationTemplate->type);
        $this->assertFalse($locationTemplate->is_port);
    }

    public function test_location_template_type_includes_special(): void
    {
        $this->assertSame('special', LocationTemplateType::SPECIAL->value);
        $this->assertSame('Special', LocationTemplateType::getNamedValues()[LocationTemplateType::SPECIAL->value]);
    }

    public function test_special_type_sets_is_port_false(): void
    {
        $locationTemplate = $this->createLocationTemplate([
            'type' => LocationTemplateType::SPECIAL->value,
            'is_port' => true,
        ]);

        $this->assertFalse($locationTemplate->is_port);
    }

    public function test_generated_location_template_file_counts_are_correct(): void
    {
        $sheet = IOFactory::load(resource_path('data-imports/Location Templates/location_templates.xlsx'))->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        $headers = array_values($rows[1]);
        unset($rows[1]);
        $records = collect($rows)->map(function (array $row) use ($headers) {
            return array_combine($headers, array_values($row));
        });

        $this->assertSame(1249, $records->count());
        $this->assertSame(625, $records->where('type', LocationTemplateType::REGULAR->value)->count());
        $this->assertSame(78, $records->where('type', LocationTemplateType::DELVE->value)->count());
        $this->assertSame(312, $records->where('type', LocationTemplateType::SPECIAL->value)->count());
        $this->assertSame(234, $records->where('type', LocationTemplateType::PORT->value)->count());
    }

    public function test_generated_location_template_file_names_and_descriptions_are_unique(): void
    {
        $sheet = IOFactory::load(resource_path('data-imports/Location Templates/location_templates.xlsx'))->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        $headers = array_values($rows[1]);
        unset($rows[1]);
        $records = collect($rows)->map(function (array $row) use ($headers) {
            return array_combine($headers, array_values($row));
        });

        $this->assertSame(1249, $records->pluck('name')->unique()->count());
        $this->assertSame(1249, $records->pluck('description')->unique()->count());
    }

    public function test_generated_location_template_file_names_do_not_end_with_numeric_suffixes(): void
    {
        $sheet = IOFactory::load(resource_path('data-imports/Location Templates/location_templates.xlsx'))->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        $headers = array_values($rows[1]);
        unset($rows[1]);
        $records = collect($rows)->map(function (array $row) use ($headers) {
            return array_combine($headers, array_values($row));
        });

        $this->assertFalse($records->pluck('name')->contains(fn (string $name) => preg_match('/\s\d{2}$/', $name) === 1));
    }

    public function test_generated_location_template_file_port_flags_match_types(): void
    {
        $sheet = IOFactory::load(resource_path('data-imports/Location Templates/location_templates.xlsx'))->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        $headers = array_values($rows[1]);
        unset($rows[1]);
        $records = collect($rows)->map(function (array $row) use ($headers) {
            return array_combine($headers, array_values($row));
        });

        $this->assertSame(0, $records->where('type', LocationTemplateType::REGULAR->value)->where('is_port', 1)->count());
        $this->assertSame(0, $records->where('type', LocationTemplateType::DELVE->value)->where('is_port', 1)->count());
        $this->assertSame(0, $records->where('type', LocationTemplateType::SPECIAL->value)->where('is_port', 1)->count());
        $this->assertSame(234, $records->where('type', LocationTemplateType::PORT->value)->where('is_port', 1)->count());
    }
}
