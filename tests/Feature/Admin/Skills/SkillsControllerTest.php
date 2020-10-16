<?php

namespace Tests\Feature\Admin\Skills;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;

class SkillsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateGameSkill;

    private $user;

    protected $gameSkill;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin([], $role);

        $this->gameSkill = $this->createGameSkill();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user      = null;
        $this->gameSkill = null;
    }

    public function testCanSeeIndexPage() {
        $this->actingAs($this->user)->visit(route('skills.list'))->see('Skills');
    }

    public function testCanSeeCreatePage() {
        $this->actingAs($this->user)->visit(route('skills.create'))->see('Create skill');
    }

    public function testCanSeeShowPage() {
        $this->actingAs($this->user)->visit(route('skills.skill', [
            'skill' => $this->gameSkill->id,
        ]))->see($this->gameSkill->name);
    }

    public function testCanSeeEditPage() {
        $this->actingAs($this->user)->visit(route('skill.edit', [
            'skill' => $this->gameSkill->id,
        ]))->see('Edit skill: ' . $this->gameSkill->name);
    }
}
