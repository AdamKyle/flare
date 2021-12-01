<?php

namespace Tests\Feature\Admin\PassiveSkills;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatePassiveSkill;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Setup\Character\CharacterFactory;
use App\Flare\Models\PassiveSkill;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;

class PassiveSkillsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreatePassiveSkill;

    private $user;

    private $passive;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin($role, []);

        $this->passive = $this->createPassiveSkill();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user = null;

        $this->passive = null;
    }

    public function testAdminCanSeePassiveSkillListPage()
    {
        $this->actingAs($this->user)->visit(route('passive.skills.list'))->see('Passive Skills');
    }

    public function testNonAdminCannotSeePassiveSkillListPage()
    {

        $user = (new CharacterFactory)->createBaseCharacter()->getUser();

        $this->actingAs($user)->visit(route('game'))->visit(route('passive.skills.list'))->see('You don\'t have permission to view that.');
    }

    public function testCanCreatePassiveSkill()
    {
        $this->actingAs($this->user)->visit(route('passive.skills.create'))->see('Create New Passive Skill')->submitForm('Create Passive Skill', [
            'name'             => 'Sample Passive Skill',
            'description'      => 'Example description',
            'max_level'        => 5,
            'hours_per_level'  => 3,
            'bonus_per_level'  => 0.05,
            'effect_type'      => PassiveSkillTypeValue::KINGDOM_DEFENCE,
            'parent_skill_id'  => $this->passive->id,
            'unlocks_at_level' => 2,
            'is_locked'        => true,
            'is_parent'        => false,
        ])->see('Created: Sample Passive Skill');

        // Make sure the Passive was actually created:
        $this->assertNotNull(PassiveSkill::where('name', 'Sample Passive Skill')->first());
    }

    public function testCannotCreatePassive()
    {

        $this->actingAs($this->user)->visit(route('passive.skills.create'))
                                    ->see('Create New Passive Skill')
                                    ->submitForm('Create Passive Skill')
                                    ->see('Missing name.')
                                    ->see('Missing description.')
                                    ->see('Missing bonus per level.')
                                    ->see('Missing effect type.')
                                    ->see('Missing length of time per level.');
    }

    public function testCanSeePassive()
    {
        $this->actingAs($this->user)->visit(route('passive.skills.skill', [
            'passiveSkill' => $this->passive->id,
        ]))->see($this->passive->name);
    }

    public function testCanUpdateThePassiveSkill() {
        $this->actingAs($this->user)->visit(route('passive.skill.edit', [
            'passiveSkill' => $this->passive->id,
        ]))->see('Edit: ' . $this->passive->name)->submitForm('Update Passive Skill', [
            'name'             => 'Sample Passive Skill',
            'description'      => 'Example description',
            'max_level'        => 5,
            'hours_per_level'  => 3,
            'bonus_per_level'  => 0.05,
            'effect_type'      => PassiveSkillTypeValue::KINGDOM_DEFENCE,
            'parent_skill_id'  => $this->passive->id,
            'unlocks_at_level' => 2,
            'is_locked'        => true,
            'is_parent'        => false,
        ])->see('Updated: Sample Passive Skill');
    }

    public function testCannotUpdatePassive() {
        $this->actingAs($this->user)->visit(route('passive.skill.edit', [
            'passiveSkill' => $this->passive->id,
        ]))->see('Edit: ' . $this->passive->name)->submitForm('Update Passive Skill', [
            'name' => null,
        ])->see('Missing name.');
    }

    public function testCanSeeExportSkills() {
        $this->actingAs($this->user)->visit(route('passive.skills.export'))->see('Export Passive Skill Data');
    }

    public function testCanSeeImportPage() {
        $this->actingAs($this->user)->visit(route('passive.skills.import'))->see('Import Passive Skill Data');
    }
}
