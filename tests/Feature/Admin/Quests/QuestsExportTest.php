<?php

namespace Tests\Feature\Admin\Quests;

use App\Admin\Quests\Exports\Sheets\QuestsSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateQuest;

class QuestsExportTest extends TestCase
{
    use CreateQuest, RefreshDatabase;

    public function test_raid_id_header_appears_exactly_once(): void
    {
        $this->createQuest(['name' => 'Exported Quest']);

        $html = (new QuestsSheet)->view()->render();

        $this->assertSame(1, substr_count($html, '<th>raid_id</th>'));
    }

    public function test_required_quest_id_header_appears_exactly_once(): void
    {
        $this->createQuest(['name' => 'Exported Quest']);

        $html = (new QuestsSheet)->view()->render();

        $this->assertSame(1, substr_count($html, '<th>required_quest_id</th>'));
    }

    public function test_before_and_after_descriptions_export_raw_markdown(): void
    {
        $this->createQuest([
            'name' => 'Markdown Quest',
            'before_completion_description' => "**Bold** and\nnewline text",
            'after_completion_description' => '_Italic_ text',
        ]);

        $html = (new QuestsSheet)->view()->render();

        $this->assertStringContainsString('**Bold** and', $html);
        $this->assertStringContainsString('_Italic_ text', $html);
        $this->assertStringNotContainsString('<br', $html);
    }

    public function test_parent_chain_quest_id_compatibility_round_trip(): void
    {
        $this->createQuest(['name' => 'Chain Compat Quest', 'parent_chain_quest_id' => 42]);

        $html = (new QuestsSheet)->view()->render();

        $this->assertStringContainsString('<td>42</td>', $html);
    }
}
