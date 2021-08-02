<?php

namespace Tests\Feature\Admin\Affixes;

use App\Admin\Exports\Affixes\AffixesExport;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\TestCase;
use App\Flare\Models\GameMap;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Location;
use Event;
use Queue;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateItemAffix;
use Tests\Setup\Character\CharacterFactory;

class AffixesControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateItemAffix,
        CreateItem;

    private $user;

    protected $affix;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin($role, []);

        $this->affix = $this->createItemAffix();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user  = null;
        $this->affix = null;
    }

    public function testCanSeeIndexPage() {
        $this->actingAs($this->user)->visit(route('affixes.list'))->see('Affixes');
    }

    public function testCanSeeCreatePage() {
        $this->actingAs($this->user)->visit(route('affixes.create'))->see('Create affix');
    }

    public function testCanSeeShowPage() {
        $this->actingAs($this->user)->visit(route('affixes.affix', [
            'affix' => $this->affix->id,
        ]))->see($this->affix->name);
    }

    public function testCanSeeEditPage() {
        $this->actingAs($this->user)->visit(route('affixes.edit', [
            'affix' => $this->affix->id,
        ]))->see('Edit affix: ' . $this->affix->name);
    }

    public function testCanDelete() {
        Queue::fake();
        Event::fake();

        $character = (new CharacterFactory())->createBaseCharacter();

        $item = $this->createItem([
            'item_suffix_id' => $this->affix->id
        ]);

        MarketBoard::create([
            'character_id'  => $character->getCharacter()->id,
            'item_id'       => $item->id,
            'listed_price'  => 1000,
            'is_locked'     => false,
        ]);

        MarketHistory::create([
            'item_id'   => $item->id,
            'sold_for'  => 1000,
        ]);

        $response = $this->actingAs($this->user)->post(route('affixes.delete', [
            'affix' => $this->affix->id,
        ]))->response;


        $this->assertNull(ItemAffix::find($this->affix->id));
    }

    public function testCanExportAffixes() {
        Excel::fake();

        $this->actingAs($this->user)->post(route('affixes.export-data'));

        Excel::assertDownloaded('affixes.xlsx', function(AffixesExport $export) {
            return true;
        });
    }

    public function testCanSeeExportAffixes() {
        $this->actingAs($this->user)->visit(route('affixes.export'))->see('Export');
    }

    public function testCanSeeAffixImportPage() {
        $this->actingAs($this->user)->visit(route('affixes.import'))->see('Import Affix Data');
    }

    public function testCanImportAffixes() {
        $this->actingAs($this->user)->post(route('affixes.import-data', [
            'affixes_import' => new UploadedFile(resource_path('data-imports/affixes.xlsx'), 'affixes.xlsx')
        ]));

        $this->assertTrue(ItemAffix::all()->isNotEmpty());
    }
}
