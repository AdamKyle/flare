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

    public function test_export_includes_elemental_penetration_after_water_atonement(): void
    {
        $map = $this->createGameMap(['name' => 'Penetration Export Map']);
        $this->createMonster([
            'name' => 'Penetrating Monster',
            'game_map_id' => $map->id,
            'fire_atonement' => 0.3,
            'ice_atonement' => 0.5,
            'water_atonement' => 0.2,
            'fire_penetration' => 0.1231,
            'ice_penetration' => 0.2462,
            'water_penetration' => 0.1236,
        ]);

        $html = (new MonstersSheet)->view()->render();

        $this->assertMatchesRegularExpression(
            '/<th>water_atonement<\/th>\s*<th>fire_penetration<\/th>\s*<th>ice_penetration<\/th>\s*<th>water_penetration<\/th>/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/<td>0\.2<\/td>\s*<td>0\.1231<\/td>\s*<td>0\.2462<\/td>\s*<td>0\.1236<\/td>/',
            $html,
        );
    }

    public function test_can_use_artifacts_is_not_exported(): void
    {
        $this->createMonster(['name' => 'No Artifacts Monster']);

        $html = (new MonstersSheet)->view()->render();

        $this->assertStringNotContainsString('can_use_artifacts', $html);
    }
}
