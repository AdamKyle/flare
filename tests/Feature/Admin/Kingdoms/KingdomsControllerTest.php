<?php

namespace Tests\Feature\Admin\Kingdoms;

use App\Admin\Exports\Kingdoms\KingdomsExport;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;
use Maatwebsite\Excel\Facades\Excel;

class KingdomsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateGameBuilding;

    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin($role, []);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user = null;
    }

    public function testCanSeeKingdomsExportPage() {
        $this->actingAs($this->user)->visit(route('kingdoms.export'))->see('Export');
    }

    public function testCanSeeKingdomsImportPage() {
        $this->actingAs($this->user)->visit(route('kingdoms.import'))->see('Import Kingdom Data');
    }
}
