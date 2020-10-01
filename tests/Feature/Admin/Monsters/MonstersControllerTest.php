<?php

namespace Tests\Feature\Admin\Monsters;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Flare\Models\Monster as Monster;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateMonster;
use Tests\Setup\CharacterSetup;

class MonstersControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateMonster;

    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin([], $role);

        $this->createMonster();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user = null;
    }

    public function testAdminCanSeeLocationsPage()
    {
        $this->actingAs($this->user)->visit(route('monsters.list'))->see('Monsters');
    }

    public function testNonAdminCannotSeeLocationsPage()
    {
        $user = $this->createUser();

        (new CharacterSetup)->setupCharacter($user)->getCharacter();

        $this->actingAs($user)->visit(route('game'))->visit(route('monsters.list'))->see('You don\'t have permission to view that.');
    }

    public function testCanSeeIndexPage() {
        $this->actingAs($this->user)->visit(route('monsters.list'))->see(Monster::first()->name);
    }

    public function testCanSeeCreatePage() {
        $this->actingAs($this->user)->visit(route('monsters.create'))->see('Create Monster');
    }

    public function testCanSeeShowPage() {
        $this->actingAs($this->user)->visit(route('monsters.monster', [
            'monster' => 1
        ]))->see(Monster::first()->name);
    }

    public function testCanSeeEditPage() {
        $this->actingAs($this->user)->visit(route('monster.edit', [
            'monster' => 1
        ]))->see(Monster::first()->name);
    }
}
