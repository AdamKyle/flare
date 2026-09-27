<?php

namespace Tests\Unit\Admin\PassiveSkills\Imports\Sheets;

use App\Admin\PassiveSkills\Imports\Sheets\PassiveSkillSheet;
use App\Flare\Models\PassiveSkill;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatePassiveSkill;

class PassiveSkillSheetTest extends TestCase
{
    use CreatePassiveSkill, RefreshDatabase;

    public function test_row_keeps_an_existing_parent_skill(): void
    {
        $parent = $this->createPassiveSkill(['name' => 'Workbook Parent']);

        (new PassiveSkillSheet)->collection(collect([
            collect(['id', 'name', 'description', 'max_level', 'hours_per_level', 'effect_type', 'parent_skill_id', 'unlocks_at_level', 'is_locked', 'is_parent']),
            collect([null, 'Workbook Child', 'Child.', 5, 1, PassiveSkillTypeValue::KINGDOM_DEFENCE, $parent->id, 2, null, null]),
        ]));

        $child = PassiveSkill::where('name', 'Workbook Child')->first();

        $this->assertSame($parent->id, $child->parent_skill_id);
        $this->assertFalse($child->is_locked);
        $this->assertFalse($child->is_parent);
    }

    public function test_row_drops_a_parent_skill_that_does_not_exist(): void
    {
        (new PassiveSkillSheet)->collection(collect([
            collect(['id', 'name', 'description', 'max_level', 'hours_per_level', 'effect_type', 'parent_skill_id', 'unlocks_at_level', 'is_locked', 'is_parent']),
            collect([null, 'Orphan Passive', 'Orphan.', 5, 1, PassiveSkillTypeValue::KINGDOM_DEFENCE, 999999, 2, false, false]),
        ]));

        $this->assertNull(PassiveSkill::where('name', 'Orphan Passive')->first()->parent_skill_id);
    }
}
