<?php

namespace Tests\Feature\Admin\Affixes;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Flare\Models\GameMap;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Location;
use Event;
use Queue;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateItemAffix;

class AffixesControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateItemAffix;

    private $user;

    protected $affix;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin([], $role);

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

        $response = $this->actingAs($this->user)->post(route('affixes.delete', [
            'affix' => $this->affix->id,
        ]))->response;


        $this->assertNull(ItemAffix::find($this->affix->id));
    }
}
