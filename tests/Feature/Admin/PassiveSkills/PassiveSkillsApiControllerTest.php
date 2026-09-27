<?php

namespace Tests\Feature\Admin\PassiveSkills;

use App\Admin\PassiveSkills\Imports\PassiveSkillsImport;
use App\Flare\Models\PassiveSkill;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Tests\TestCase;
use Tests\Traits\CreatePassiveSkill;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class PassiveSkillsApiControllerTest extends TestCase
{
    use CreatePassiveSkill, CreateRole, CreateUser, RefreshDatabase;

    public function test_index_rejects_unauthenticated_request(): void
    {
        $this->getJson('/api/admin/passive-skills')->assertUnauthorized();
    }

    public function test_index_rejects_non_admin_request(): void
    {
        $this->actingAs($this->createUser())->getJson('/api/admin/passive-skills')->assertForbidden();
    }

    public function test_index_search_filters_by_name(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createPassiveSkill(['name' => 'Kingdom Management']);
        $this->createPassiveSkill(['name' => 'Master Farmer']);

        $response = $this->actingAs($admin)->getJson('/api/admin/passive-skills?search_text=Farm');

        $this->assertSame(['Master Farmer'], array_column($response->json('data'), 'name'));
    }

    public function test_index_sorts_by_max_level_descending(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->createPassiveSkill(['name' => 'Short', 'max_level' => 5]);
        $this->createPassiveSkill(['name' => 'Long', 'max_level' => 50]);

        $response = $this->actingAs($admin)->getJson('/api/admin/passive-skills?sort_key=max_level&sort_direction=desc');

        $this->assertSame('Long', $response->json('data.0.name'));
    }

    public function test_tree_returns_all_passive_skills_without_pagination(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $this->createPassiveSkill(['name' => 'Tree Skill One']);
        $this->createPassiveSkill(['name' => 'Tree Skill Two']);

        $response = $this->actingAs($admin)->getJson('/api/admin/passive-skills/tree?per_page=1');

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    public function test_tree_returns_factual_parent_and_unlock_level(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $parent = $this->createPassiveSkill(['name' => 'Tree Parent']);
        $child = $this->createPassiveSkill([
            'name' => 'Tree Child',
            'parent_skill_id' => $parent->id,
            'unlocks_at_level' => 4,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/passive-skills/tree');
        $childData = collect($response->json())->firstWhere('id', $child->id);

        $this->assertSame($parent->id, $childData['parent_id']);
        $this->assertSame(4, $childData['unlocks_at_level']);
    }

    public function test_tree_returns_factual_effect_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $passiveSkill = $this->createPassiveSkill([
            'effect_type' => PassiveSkillTypeValue::MASTER_FARMER,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/passive-skills/tree');
        $passiveSkillData = collect($response->json())->firstWhere('id', $passiveSkill->id);

        $this->assertSame(PassiveSkillTypeValue::MASTER_FARMER, $passiveSkillData['effect_type']);
    }

    public function test_tree_is_ordered_deterministically_by_id(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $first = $this->createPassiveSkill(['name' => 'Zulu']);
        $second = $this->createPassiveSkill(['name' => 'Alpha']);

        $response = $this->actingAs($admin)->getJson('/api/admin/passive-skills/tree');

        $this->assertSame([$first->id, $second->id], array_column($response->json(), 'id'));
    }

    public function test_show_returns_parent_and_child_skills(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $parent = $this->createPassiveSkill(['name' => 'Parent Passive']);
        $passiveSkill = $this->createPassiveSkill(['name' => 'Middle Passive', 'parent_skill_id' => $parent->id]);
        $child = $this->createPassiveSkill(['name' => 'Child Passive', 'parent_skill_id' => $passiveSkill->id, 'unlocks_at_level' => 3]);

        $response = $this->actingAs($admin)->getJson('/api/admin/passive-skills/'.$passiveSkill->id);

        $response->assertOk()->assertJson([
            'parent' => ['id' => $parent->id, 'name' => 'Parent Passive'],
            'child_skills' => [['id' => $child->id, 'name' => 'Child Passive', 'unlocks_at_level' => 3]],
        ]);
    }

    public function test_edit_returns_current_form_values(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $passiveSkill = $this->createPassiveSkill(['name' => 'Edit Target', 'hours_per_level' => 4]);

        $response = $this->actingAs($admin)->getJson('/api/admin/passive-skills/'.$passiveSkill->id.'/edit');

        $response->assertOk()->assertJson(['name' => 'Edit Target', 'hours_per_level' => 4]);
    }

    public function test_options_return_named_effects_and_passive_skills(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $passiveSkill = $this->createPassiveSkill(['name' => 'Option Passive']);

        $response = $this->actingAs($admin)->getJson('/api/admin/passive-skills/options');

        $this->assertContains(['value' => PassiveSkillTypeValue::MASTER_FARMER, 'name' => 'Master Farmer'], $response->json('effects'));
        $this->assertContains(['id' => $passiveSkill->id, 'name' => 'Option Passive'], $response->json('passive_skills'));
    }

    public function test_store_persists_travel_time_reductions_and_parent(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $parent = $this->createPassiveSkill(['name' => 'Capital Parent']);

        $response = $this->actingAs($admin)->postJson('/api/admin/passive-skills', [
            'name' => 'Swift Couriers',
            'description' => 'Faster requests.',
            'max_level' => 10,
            'effect_type' => PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_BUILD_TRAVEL_TIME_REDUCTION,
            'capital_city_building_request_travel_time_reduction' => 0.05,
            'capital_city_unit_request_travel_time_reduction' => 0.04,
            'resource_request_time_reduction' => 0.03,
            'parent_skill_id' => $parent->id,
            'unlocks_at_level' => 2,
            'hours_per_level' => 6,
            'is_locked' => true,
            'is_parent' => false,
        ]);

        $passiveSkill = PassiveSkill::where('name', 'Swift Couriers')->first();

        $response->assertCreated();
        $this->assertSame(0.05, $passiveSkill->capital_city_building_request_travel_time_reduction);
        $this->assertSame(0.04, $passiveSkill->capital_city_unit_request_travel_time_reduction);
        $this->assertSame(0.03, $passiveSkill->resource_request_time_reduction);
        $this->assertSame($parent->id, $passiveSkill->parent_skill_id);
    }

    public function test_update_persists_locked_and_parent_flags(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $passiveSkill = $this->createPassiveSkill(['name' => 'Flag Target', 'is_locked' => false, 'is_parent' => true]);

        $response = $this->actingAs($admin)->putJson('/api/admin/passive-skills/'.$passiveSkill->id, [
            'name' => 'Flag Target',
            'description' => 'Flags flipped.',
            'max_level' => 5,
            'effect_type' => PassiveSkillTypeValue::KINGDOM_DEFENCE,
            'hours_per_level' => 1,
            'is_locked' => true,
            'is_parent' => false,
        ]);

        $passiveSkill->refresh();

        $response->assertOk();
        $this->assertTrue($passiveSkill->is_locked);
        $this->assertFalse($passiveSkill->is_parent);
    }

    public function test_store_rejects_an_unknown_effect_type(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->postJson('/api/admin/passive-skills', [
            'name' => 'Broken Passive',
            'description' => 'Invalid effect.',
            'max_level' => 5,
            'effect_type' => 999,
            'hours_per_level' => 1,
            'is_locked' => false,
            'is_parent' => false,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['effect_type']);
    }

    public function test_update_rejects_a_passive_skill_belonging_to_itself(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $passiveSkill = $this->createPassiveSkill(['name' => 'Self Parent']);

        $response = $this->actingAs($admin)->putJson('/api/admin/passive-skills/'.$passiveSkill->id, [
            'name' => 'Self Parent',
            'description' => 'Points at itself.',
            'max_level' => 5,
            'effect_type' => PassiveSkillTypeValue::KINGDOM_DEFENCE,
            'parent_skill_id' => $passiveSkill->id,
            'hours_per_level' => 1,
            'is_locked' => false,
            'is_parent' => false,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['parent_skill_id']);
    }

    public function test_import_invokes_the_passive_skills_workbook_boundary(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $file = UploadedFile::fake()->create('passive_skills.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        Excel::shouldReceive('import')->once()->with(Mockery::type(PassiveSkillsImport::class), Mockery::type(UploadedFile::class));

        $response = $this->actingAs($admin)->post('/api/admin/passive-skills/import', ['passives_import' => $file]);

        $response->assertOk()->assertJson(['message' => 'Passive Skills imported successfully.']);
    }
}
