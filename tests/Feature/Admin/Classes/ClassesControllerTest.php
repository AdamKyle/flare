<?php

namespace Tests\Feature\Admin\Classes;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;

class ClassesControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateClass;

    private $user;

    protected $class;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin($role, []);

        $this->class = $this->createClass();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user  = null;
        $this->race  = null;
    }

    public function testCanSeeIndexPage() {
        $this->actingAs($this->user)->visit(route('classes.list'))->see('Classes');
    }

    public function testCanSeeCreatePage() {
        $this->actingAs($this->user)->visit(route('classes.create'))->see('Create class');
    }

    public function testCanSeeEditPage() {
        $this->actingAs($this->user)->visit(route('classes.edit', [
            'class' => $this->class->id,
        ]))->see($this->class->name);
    }

    public function testCanSeeShowPage() {
        $this->actingAs($this->user)->visit(route('classes.class', [
            'class' => $this->class->id,
        ]))->see($this->class->name);
    }
}
