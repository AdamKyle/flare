<?php

namespace Tests\Unit\Admin\Npcs\Imports\Sheets;

use App\Admin\Npcs\Imports\Sheets\NpcsSheet;
use App\Flare\Models\Npc;
use App\Game\Npcs\Values\NpcType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateNpc;

class NpcsSheetTest extends TestCase
{
    use CreateGameMap, CreateNpc, RefreshDatabase;

    public function test_valid_workbook_row_creates_a_new_npc(): void
    {
        $gameMap = $this->createGameMap();

        (new NpcsSheet)->collection(collect([
            collect(['id', 'name', 'real_name', 'type', 'game_map_id', 'x_position', 'y_position']),
            collect([null, 'ImportedNpc', 'Imported Npc', NpcType::QUEST_GIVER->value, $gameMap->name, 16, 16]),
        ]));

        $npc = Npc::where('real_name', 'Imported Npc')->first();

        $this->assertNotNull($npc);
        $this->assertSame($gameMap->id, $npc->game_map_id);
        $this->assertSame(NpcType::QUEST_GIVER->value, $npc->type);
    }

    public function test_valid_workbook_row_updates_an_existing_npc_by_id(): void
    {
        $gameMap = $this->createGameMap();
        $npc = $this->createNpc(['real_name' => 'Original Name', 'game_map_id' => $gameMap->id]);

        (new NpcsSheet)->collection(collect([
            collect(['id', 'real_name', 'type', 'game_map_id', 'x_position', 'y_position']),
            collect([$npc->id, 'Updated Name', NpcType::SUMMONER->value, $gameMap->name, 20, 20]),
        ]));

        $npc->refresh();

        $this->assertSame('Updated Name', $npc->real_name);
        $this->assertSame(NpcType::SUMMONER->value, $npc->type);
        $this->assertSame(20, $npc->x_position);
    }

    public function test_invalid_npc_type_invalidates_the_entire_import(): void
    {
        $gameMap = $this->createGameMap();

        (new NpcsSheet)->collection(collect([
            collect(['id', 'real_name', 'type', 'game_map_id', 'x_position', 'y_position']),
            collect([null, 'Bad Type Npc', 99999, $gameMap->name, 16, 16]),
        ]));

        $this->assertNull(Npc::where('real_name', 'Bad Type Npc')->first());
    }

    public function test_invalid_later_row_prevents_earlier_row_from_being_written(): void
    {
        $gameMap = $this->createGameMap();

        (new NpcsSheet)->collection(collect([
            collect(['id', 'real_name', 'type', 'game_map_id', 'x_position', 'y_position']),
            collect([null, 'Valid Earlier Npc', NpcType::QUEST_GIVER->value, $gameMap->name, 16, 16]),
            collect([null, 'Invalid Later Npc', NpcType::QUEST_GIVER->value, 'Does Not Exist Map', 16, 16]),
        ]));

        $this->assertNull(Npc::where('real_name', 'Valid Earlier Npc')->first());
        $this->assertNull(Npc::where('real_name', 'Invalid Later Npc')->first());
    }

    public function test_blank_row_stops_processing_without_touching_rows_after_it(): void
    {
        $gameMap = $this->createGameMap();

        (new NpcsSheet)->collection(collect([
            collect(['id', 'name', 'real_name', 'type', 'game_map_id', 'x_position', 'y_position']),
            collect([null, 'ValidBeforeBlank', 'Valid Before Blank', NpcType::QUEST_GIVER->value, $gameMap->name, 16, 16]),
            collect([null, null, null, null, null, null, null]),
            collect([null, 'ShouldNotBeReached', 'Should Not Be Reached', NpcType::QUEST_GIVER->value, $gameMap->name, 16, 16]),
        ]));

        $this->assertNotNull(Npc::where('real_name', 'Valid Before Blank')->first());
        $this->assertNull(Npc::where('real_name', 'Should Not Be Reached')->first());
    }
}
