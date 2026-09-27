<?php

namespace Tests\Feature\Admin\Skills;

use App\Admin\Services\AssignSkillService;
use App\Admin\Skills\Imports\SkillsImport;
use App\Flare\Models\GameSkill;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class SkillsApiControllerTest extends TestCase
{
    use CreateClass, CreateGameSkill, CreateRole, CreateUser, RefreshDatabase;

    public function test_index_rejects_unauthenticated_request(): void
    {
        $response = $this->getJson('/api/admin/skills');

        $response->assertUnauthorized();
    }

    public function test_index_rejects_non_admin_request(): void
    {
        $response = $this->actingAs($this->createUser())->getJson('/api/admin/skills');

        $response->assertForbidden();
    }

    public function test_index_search_filters_by_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameSkill(['name' => 'Accuracy']);
        $this->createGameSkill(['name' => 'Dodge']);

        $response = $this->actingAs($admin)->getJson('/api/admin/skills?search_text=Accu');

        $this->assertSame(['Accuracy'], array_column($response->json('data'), 'name'));
    }

    public function test_index_sorts_by_max_level_descending(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createGameSkill(['name' => 'Low Skill', 'max_level' => 10]);
        $this->createGameSkill(['name' => 'High Skill', 'max_level' => 400]);

        $response = $this->actingAs($admin)->getJson('/api/admin/skills?sort_key=max_level&sort_direction=desc');

        $this->assertSame('High Skill', $response->json('data.0.name'));
    }

    public function test_index_rejects_a_sort_key_outside_the_allowed_columns(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->getJson('/api/admin/skills?sort_key=description');

        $response->assertUnprocessable()->assertJsonValidationErrors(['sort_key']);
    }

    public function test_show_returns_the_skill_with_its_class(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Fighter']);
        $gameSkill = $this->createGameSkill(['name' => 'Fighters Might', 'game_class_id' => $gameClass->id]);

        $response = $this->actingAs($admin)->getJson('/api/admin/skills/'.$gameSkill->id);

        $response->assertOk()->assertJson([
            'name' => 'Fighters Might',
            'game_class' => ['id' => $gameClass->id, 'name' => 'Fighter'],
        ]);
    }

    public function test_edit_returns_current_form_values(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameSkill = $this->createGameSkill(['name' => 'Edit Target', 'is_locked' => true]);

        $response = $this->actingAs($admin)->getJson('/api/admin/skills/'.$gameSkill->id.'/edit');

        $response->assertOk()->assertJson(['name' => 'Edit Target', 'is_locked' => true]);
    }

    public function test_options_return_skill_types_and_classes(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Ranger']);

        $response = $this->actingAs($admin)->getJson('/api/admin/skills/options');

        $this->assertContains(SkillTypeValue::ALCHEMY->value, $response->json('types'));
        $this->assertContains(['id' => $gameClass->id, 'name' => 'Ranger'], $response->json('classes'));
    }

    public function test_store_creates_a_class_skill(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameClass = $this->createClass(['name' => 'Heretic']);

        $response = $this->actingAs($admin)->postJson('/api/admin/skills', [
            'name' => 'Heretics Wrath',
            'description' => 'A class skill.',
            'max_level' => 400,
            'type' => SkillTypeValue::EFFECTS_CLASS->value,
            'game_class_id' => $gameClass->id,
            'class_bonus' => 0.01,
            'can_train' => true,
            'is_locked' => false,
        ]);

        $response->assertCreated();
        $this->assertSame($gameClass->id, GameSkill::where('name', 'Heretics Wrath')->first()->game_class_id);
    }

    public function test_update_persists_boolean_changes(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $gameSkill = $this->createGameSkill(['name' => 'Locked Skill', 'can_train' => true, 'is_locked' => false]);

        $response = $this->actingAs($admin)->putJson('/api/admin/skills/'.$gameSkill->id, [
            'name' => 'Locked Skill',
            'description' => 'Now locked.',
            'max_level' => 5,
            'type' => SkillTypeValue::TRAINING->value,
            'can_train' => false,
            'is_locked' => true,
        ]);

        $gameSkill->refresh();

        $response->assertOk();
        $this->assertFalse($gameSkill->can_train);
        $this->assertSame(1, $gameSkill->is_locked);
    }

    public function test_store_rejects_an_unknown_skill_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->postJson('/api/admin/skills', [
            'name' => 'Broken Skill',
            'description' => 'Invalid type.',
            'max_level' => 5,
            'type' => 999,
            'can_train' => true,
            'is_locked' => false,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['type']);
    }

    public function test_import_invokes_the_skills_workbook_boundary(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $file = UploadedFile::fake()->create('skills.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->instance(AssignSkillService::class, Mockery::mock(AssignSkillService::class, function ($mock) {
            $mock->shouldReceive('assignSkills');
        }));
        Excel::shouldReceive('import')->once()->with(Mockery::type(SkillsImport::class), Mockery::type(UploadedFile::class), null, ExcelWriter::XLSX);

        $response = $this->actingAs($admin)->post('/api/admin/skills/import', ['skills_import' => $file]);

        $response->assertOk()->assertJson(['message' => 'Skills imported successfully.']);
    }

    public function test_import_assigns_imported_skills_to_existing_characters(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $file = UploadedFile::fake()->create('skills.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        Excel::shouldReceive('import');
        $this->instance(AssignSkillService::class, Mockery::mock(AssignSkillService::class, function ($mock) {
            $mock->shouldReceive('assignSkills')->once();
        }));

        $response = $this->actingAs($admin)->post('/api/admin/skills/import', ['skills_import' => $file]);

        $response->assertOk();
    }
}
