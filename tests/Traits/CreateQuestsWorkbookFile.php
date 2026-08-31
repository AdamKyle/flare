<?php

namespace Tests\Traits;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait CreateQuestsWorkbookFile
{
    private const QUESTS_WORKBOOK_HEADERS = [
        'id', 'name', 'npc_id', 'item_id', 'raid_id', 'required_quest_id', 'parent_chain_quest_id',
        'required_quest_chain', 'reincarnated_times', 'access_to_map_id', 'gold_dust_cost', 'shard_cost',
        'gold_cost', 'copper_coin_cost', 'reward_item', 'reward_gold_dust', 'reward_shards', 'reward_gold',
        'reward_xp', 'unlocks_skill', 'unlocks_skill_type', 'is_parent', 'parent_quest_id',
        'secondary_required_item', 'faction_game_map_id', 'required_faction_level', 'before_completion_description',
        'after_completion_description', 'unlocks_feature', 'unlocks_passive_id', 'only_for_event',
        'assisting_npc_id', 'required_fame_level',
    ];

    /**
     * Build a real, on-disk Quests import workbook fixture from named row values.
     *
     * @param  array<int, array<string, mixed>>  $rowsByHeaderName  Each row's values, keyed by header name; any header not present in a row is written blank.
     */
    public function createQuestsWorkbookFile(array $rowsByHeaderName): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(self::QUESTS_WORKBOOK_HEADERS, null, 'A1');

        foreach ($rowsByHeaderName as $index => $row) {
            $orderedValues = array_map(fn (string $header) => $row[$header] ?? null, self::QUESTS_WORKBOOK_HEADERS);
            $sheet->fromArray($orderedValues, null, 'A'.($index + 2));
        }

        $path = tempnam(sys_get_temp_dir(), 'quests_import').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'quests.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
