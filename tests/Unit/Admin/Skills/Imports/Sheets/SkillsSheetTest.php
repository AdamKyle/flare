<?php

namespace Tests\Unit\Admin\Skills\Imports\Sheets;

use App\Admin\Skills\Imports\Sheets\SkillsSheet;
use App\Flare\Models\GameSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;

class SkillsSheetTest extends TestCase
{
    use CreateClass, CreateGameSkill, RefreshDatabase;

    public function test_new_row_creates_a_skill_with_unset_flags_defaulted_to_false(): void
    {
        (new SkillsSheet)->collection(collect([
            collect(['id', 'name', 'description', 'max_level', 'type', 'game_class_id', 'can_train', 'is_locked']),
            collect([null, 'Imported Skill', 'From the workbook.', 10, 0, null, null, null]),
        ]));

        $gameSkill = GameSkill::where('name', 'Imported Skill')->first();

        $this->assertFalse($gameSkill->can_train);
        $this->assertSame(0, $gameSkill->is_locked);
    }

    public function test_row_with_existing_id_updates_that_skill(): void
    {
        $gameSkill = $this->createGameSkill(['name' => 'Old Name']);

        (new SkillsSheet)->collection(collect([
            collect(['id', 'name', 'description', 'max_level', 'type', 'game_class_id', 'can_train', 'is_locked']),
            collect([$gameSkill->id, 'New Name', 'Updated.', 20, 0, null, true, false]),
        ]));

        $this->assertSame('New Name', $gameSkill->refresh()->name);
    }

    public function test_unknown_class_reference_is_dropped_from_the_row(): void
    {
        $gameSkill = $this->createGameSkill(['name' => 'Class Skill', 'game_class_id' => $this->createClass()->id]);
        $originalClassId = $gameSkill->game_class_id;

        (new SkillsSheet)->collection(collect([
            collect(['id', 'name', 'description', 'max_level', 'type', 'game_class_id', 'can_train', 'is_locked']),
            collect([$gameSkill->id, 'Class Skill', 'Updated.', 20, 0, 999999, true, false]),
        ]));

        $this->assertSame($originalClassId, $gameSkill->refresh()->game_class_id);
    }
}
