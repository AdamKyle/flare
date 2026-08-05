<?php

namespace Tests\Feature\Game\Core\Controllers;

use App\Flare\Models\ItemSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItem;

class ItemsControllerTest extends TestCase
{
    use CreateItem, RefreshDatabase;

    public function test_item_show_page_renders_item_details(): void
    {
        $item = $this->createItem(['name' => 'Ironclad Helm', 'type' => 'helmet']);

        $this->visit('/items/'.$item->id)
            ->see('Ironclad Helm');
    }

    public function test_item_show_page_renders_item_skills_table_when_item_has_skills(): void
    {
        $itemSkill = ItemSkill::create([
            'name' => 'Blade Mastery',
            'description' => 'Increases blade proficiency.',
            'max_level' => 10,
            'total_kills_needed' => 500,
        ]);

        $item = $this->createItem(['name' => 'Ironclad Sword', 'type' => 'weapon', 'item_skill_id' => $itemSkill->id]);

        $this->visit('/items/'.$item->id)
            ->see('Blade Mastery')
            ->see('Increases blade proficiency.');
    }
}
