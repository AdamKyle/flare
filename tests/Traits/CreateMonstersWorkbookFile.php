<?php

namespace Tests\Traits;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

trait CreateMonstersWorkbookFile
{
    private const MONSTERS_WORKBOOK_HEADERS = [
        'id', 'name', 'damage_stat', 'game_map_id', 'max_level', 'xp', 'gold', 'health_range',
        'attack_range', 'drop_check', 'only_for_location_type', 'str', 'dur', 'dex', 'chr', 'int',
        'agi', 'focus', 'ac', 'accuracy', 'dodge', 'criticality', 'ambush_chance', 'ambush_resistance',
        'counter_chance', 'counter_resistance', 'can_cast', 'max_spell_damage', 'casting_accuracy',
        'spell_evasion', 'max_affix_damage', 'affix_resistance', 'healing_percentage',
        'entrancing_chance', 'devouring_light_chance', 'devouring_darkness_chance',
        'life_stealing_resistance', 'quest_item_id', 'quest_item_drop_chance', 'is_celestial_entity',
        'celestial_type', 'gold_cost', 'gold_dust_cost', 'shards', 'is_raid_monster', 'is_raid_boss',
        'raid_special_attack_type', 'fire_atonement', 'ice_atonement', 'water_atonement',
    ];

    /**
     * Build a real, on-disk Monsters import workbook fixture from named row values.
     *
     * @param array<int, array<string, mixed>> $rowsByHeaderName Each row's values, keyed by header name; any header not present in a row is written blank.
     */
    public function createMonstersWorkbookFile(array $rowsByHeaderName): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(self::MONSTERS_WORKBOOK_HEADERS, null, 'A1');

        foreach ($rowsByHeaderName as $index => $row) {
            $orderedValues = array_map(fn (string $header) => $row[$header] ?? null, self::MONSTERS_WORKBOOK_HEADERS);
            $sheet->fromArray($orderedValues, null, 'A'.($index + 2));
        }

        $path = tempnam(sys_get_temp_dir(), 'monsters_import').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'monsters.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
