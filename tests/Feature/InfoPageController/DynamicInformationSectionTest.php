<?php

namespace Tests\Feature\InfoPageController;

use App\Flare\Models\InfoPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateClass;
use Tests\Traits\CreateGameSkill;

class DynamicInformationSectionTest extends TestCase
{
    use CreateClass, CreateGameSkill, RefreshDatabase;

    public function test_page_renders_class_skills_table_for_stored_live_wire_component_alias(): void
    {
        $class = $this->createClass(['name' => 'Warrior']);
        $this->createGameSkill(['name' => 'Shield Bash', 'game_class_id' => $class->id]);

        InfoPage::create([
            'page_name' => 'class-skills-page',
            'page_sections' => [
                [
                    'order' => 1,
                    'content' => '<p>Class skills below.</p>',
                    'content_image_path' => null,
                    'live_wire_component' => 'info.skills.class-skills',
                    'item_table_type' => null,
                ],
            ],
        ]);

        $this->visit('/information/class-skills-page')
            ->see('Shield Bash')
            ->see('Warrior');
    }

    public function test_page_with_unknown_live_wire_component_alias_renders_safely(): void
    {
        InfoPage::create([
            'page_name' => 'unknown-alias-page',
            'page_sections' => [
                [
                    'order' => 1,
                    'content' => '<p>Nothing dynamic here.</p>',
                    'content_image_path' => null,
                    'live_wire_component' => 'info.does-not-exist',
                    'item_table_type' => null,
                ],
            ],
        ]);

        $response = $this->call('GET', '/information/unknown-alias-page');

        $response->assertOk();
        $response->assertSee('Nothing dynamic here.');
    }
}
