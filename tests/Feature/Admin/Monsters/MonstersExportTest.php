<?php

namespace Tests\Feature\Admin\Monsters;

use App\Admin\Monsters\Exports\Sheets\MonstersSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateMonster;

class MonstersExportTest extends TestCase
{
    use CreateGameMap, CreateMonster, RefreshDatabase;

    public function test_export_contains_current_meaningful_fields(): void
    {
        $map = $this->createGameMap(['name' => 'Export Map']);
        $this->createMonster(['name' => 'Exported Monster', 'game_map_id' => $map->id, 'xp' => 77]);

        $html = (new MonstersSheet)->view()->render();

        $this->assertStringContainsString('Exported Monster', $html);
        $this->assertStringContainsString('Export Map', $html);
        $this->assertStringContainsString('<th>damage_stat</th>', $html);
        $this->assertStringContainsString('<th>quest_item_id</th>', $html);
    }

    public function test_can_use_artifacts_is_not_exported(): void
    {
        $this->createMonster(['name' => 'No Artifacts Monster']);

        $html = (new MonstersSheet)->view()->render();

        $this->assertStringNotContainsString('can_use_artifacts', $html);
    }
}
