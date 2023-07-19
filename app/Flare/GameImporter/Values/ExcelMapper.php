<?php

namespace App\Flare\GameImporter\Values;

use App\Admin\Import\Affixes\AffixesImport;
use App\Admin\Import\Classes\ClassImport;
use App\Admin\Import\ClassSpecials\ClassSpecialsImport;
use App\Admin\Import\Events\EventsImport;
use App\Admin\Import\GuideQuests\GuideQuests;
use App\Admin\Import\Items\ItemsImport;
use App\Admin\Import\ItemSkills\ItemSkillsImport;
use App\Admin\Import\Kingdoms\KingdomsImport;
use App\Admin\Import\Locations\LocationsImport;
use App\Admin\Import\Monsters\MonstersImport;
use App\Admin\Import\Npcs\NpcsImport;
use App\Admin\Import\PassiveSkills\PassiveSkillsImport;
use App\Admin\Import\Quests\QuestsImport;
use App\Admin\Import\Races\RacesImport;
use App\Admin\Import\Raids\RaidsImport;
use App\Admin\Import\Skills\SkillsImport;
use Maatwebsite\Excel\Facades\Excel;

class ExcelMapper {

    /**
     * @var array $map
     */
    private array $map = [
        'Admin Section' => [
            GuideQuests::class,
            RaidsImport::class,
            EventsImport::class,
        ],
        'Affixes'       => AffixesImport::class,
        'Core Imports'  => [
            ClassImport::class,
            RacesImport::class,
        ],
        'Items'         => ItemsImport::class,
        'Monsters'      => MonstersImport::class,
        'Skills'        => [
            ItemSkillsImport::class,
            SkillsImport::class,
        ],
        'Kingdoms'      => [
            PassiveSkillsImport::class,
            KingdomsImport::class,
        ],
        '.'             => [
            ClassSpecialsImport::class,
            LocationsImport::class,
            NpcsImport::class,
            QuestsImport::class,
        ]
    ];

    /**
     * Import the files based on the directory.
     *
     * @param string $dirName
     * @param string $path
     * @param integer $index
     * @return void
     */
    public function importFile(string $dirName, string $path, int $index): void {
        foreach ($this->map as $directory => $importMap) {
            if ($directory === $dirName) {
                if (is_array($importMap)) {
                    Excel::import(new $importMap[$index], $path);

                    continue;
                }

                Excel::import(new $importMap, $path);
            }
        }
    }
}