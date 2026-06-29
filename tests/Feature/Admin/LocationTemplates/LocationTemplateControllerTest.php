<?php

namespace Tests\Feature\Admin\LocationTemplates;

use App\Admin\Exports\LocationTemplates\Sheets\LocationTemplatesSheet as ExportLocationTemplatesSheet;
use App\Admin\Import\LocationTemplates\Sheets\LocationTemplatesSheet as ImportLocationTemplatesSheet;
use App\Flare\Models\LocationTemplate;
use App\Flare\View\Livewire\Admin\LocationTemplates\LocationTemplatesTable;
use App\Flare\Values\LocationTemplateType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;
use Tests\Traits\CreateLocationTemplate;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class LocationTemplateControllerTest extends TestCase
{
    use CreateLocationTemplate, CreateRole, CreateUser, RefreshDatabase;

    public function testLocationTemplateCanBeCreatedWithValidFields(): void
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

    public function testRequestValidationRejectsMissingName(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->post(route('admin.location-templates.store'), [
            'id' => 0,
            'description' => 'Missing name description',
            'type' => LocationTemplateType::REGULAR->value,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function testRequestValidationRejectsMissingDescription(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->post(route('admin.location-templates.store'), [
            'id' => 0,
            'name' => 'Missing Description',
            'type' => LocationTemplateType::REGULAR->value,
        ]);

        $response->assertSessionHasErrors('description');
    }

    public function testRequestValidationRejectsMissingType(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->post(route('admin.location-templates.store'), [
            'id' => 0,
            'name' => 'Missing Type',
            'description' => 'Missing type description',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function testRequestValidationRejectsDuplicateName(): void
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

    public function testRequestValidationRejectsDuplicateDescription(): void
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

    public function testAdminCanCreateLocationTemplate(): void
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

    public function testAdminCanCreateSpecialLocationTemplate(): void
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

    public function testAdminCanUpdateLocationTemplate(): void
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

    public function testAdminCanUpdateLocationTemplateToSpecial(): void
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

    public function testAdminCanViewLocationTemplate(): void
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

    public function testSpecialLocationTemplateDisplaysOnShowPage(): void
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

    public function testLocationTemplateTableLinksNameToShowAndKeepsEditActionOnly(): void
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

    public function testSpecialLocationTemplateDisplaysInLivewireTable(): void
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

    public function testLocationTemplatesSidebarHasIcon(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $this->actingAs($admin)
            ->visitRoute('admin.location-templates.list')
            ->see('<span class="ra ra-map"></span>', false)
            ->see('Location Templates');
    }

    public function testAdminCanDeleteLocationTemplate(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $locationTemplate = $this->createLocationTemplate();

        $this->actingAs($admin)->post(route('admin.location-templates.delete', ['locationTemplate' => $locationTemplate]));

        $this->assertNull(LocationTemplate::find($locationTemplate->id));
    }

    public function testExportHasExpectedHeadingsAndShape(): void
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

    public function testExportIncludesSpecialLocationTemplates(): void
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

    public function testImportCreatesTemplates(): void
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

    public function testImportCreatesSpecialTemplates(): void
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

    public function testRequestValidationRejectsInvalidType(): void
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

    public function testImportRejectsDuplicateNames(): void
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

    public function testImportRejectsDuplicateDescriptions(): void
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

    public function testPortTypeSetsIsPortTrue(): void
    {
        $locationTemplate = $this->createLocationTemplate([
            'type' => LocationTemplateType::PORT->value,
            'is_port' => false,
        ]);

        $this->assertTrue($locationTemplate->is_port);
    }

    public function testRegularTypeSetsIsPortFalse(): void
    {
        $locationTemplate = $this->createLocationTemplate([
            'type' => LocationTemplateType::REGULAR->value,
            'is_port' => true,
        ]);

        $this->assertFalse($locationTemplate->is_port);
    }

    public function testDelveTypeIsSeparateFromRegularAndPortPool(): void
    {
        $locationTemplate = $this->createLocationTemplate([
            'type' => LocationTemplateType::DELVE->value,
        ]);

        $this->assertSame(LocationTemplateType::DELVE->value, $locationTemplate->type);
        $this->assertFalse($locationTemplate->is_port);
    }

    public function testLocationTemplateTypeIncludesSpecial(): void
    {
        $this->assertSame('special', LocationTemplateType::SPECIAL->value);
        $this->assertSame('Special', LocationTemplateType::getNamedValues()[LocationTemplateType::SPECIAL->value]);
    }

    public function testSpecialTypeSetsIsPortFalse(): void
    {
        $locationTemplate = $this->createLocationTemplate([
            'type' => LocationTemplateType::SPECIAL->value,
            'is_port' => true,
        ]);

        $this->assertFalse($locationTemplate->is_port);
    }

    public function testGeneratedLocationTemplateFileCountsAreCorrect(): void
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

    public function testGeneratedLocationTemplateFileNamesAndDescriptionsAreUnique(): void
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

    public function testGeneratedLocationTemplateFilePortFlagsMatchTypes(): void
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
