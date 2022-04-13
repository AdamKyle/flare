<?php

namespace App\Admin\Import\Npcs\Sheets;


use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Flare\Models\NpcCommand;
use App\Flare\Models\Npc;

class NpcCommandsSheet implements ToCollection {

    public function collection(Collection $rows) {
        foreach ($rows as $index => $row) {
            if ($index !== 0) {

                $commandData = [
                    'id'           => $row[0],
                    'npc_id'       => $row[1],
                    'command'      => $row[2],
                    'command_type' => $row[3],
                ];

                NpcCommand::UpdateOrCreate(['id' => $commandData['id']], $commandData);
            }
        }
    }
}
