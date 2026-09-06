<?php

namespace App\Flare\GameImporter\Values;

use App\Admin\Classes\Imports\ClassesImport;
use App\Admin\ClassMasteries\Imports\ClassMasteriesImport;
use App\Admin\Import\Affixes\AffixesImport;
use App\Admin\Import\GuideQuests\GuideQuests;
use App\Admin\Import\ItemSkills\ItemSkillsImport;
use App\Admin\Import\Kingdoms\KingdomsImport;
use App\Admin\Import\LocationTemplates\LocationTemplatesImport;
use App\Admin\Import\Monsters\MonstersImport;
use App\Admin\Import\PassiveSkills\PassiveSkillsImport;
use App\Admin\Import\Quests\QuestsImport;
use App\Admin\Import\Raids\RaidsImport;
use App\Admin\Import\Skills\SkillsImport;
use App\Admin\Items\Imports\ItemsImport;
use App\Admin\Locations\Imports\LocationsImport;
use App\Admin\Npcs\Imports\NpcsImport;
use App\Admin\Races\Imports\RacesImport;
use Maatwebsite\Excel\Facades\Excel;

class ExcelMapper
{
    private array $map = [
        'Admin Section' => GuideQuests::class,
        'Raids' => RaidsImport::class,
        'Affixes' => AffixesImport::class,
        'Core Imports' => [
            RacesImport::class,
            ClassesImport::class,
            ClassMasteriesImport::class,
        ],
        'Items' => ItemsImport::class,
        'Weapons' => ItemsImport::class,
        'Armour' => ItemsImport::class,
        'Monsters' => MonstersImport::class,
        'Skills' => [
            ItemSkillsImport::class,
            SkillsImport::class,
        ],
        'Kingdoms' => KingdomsImport::class,
        'Kingdom Passive Skills' => PassiveSkillsImport::class,
        'Quests' => [
            GuideQuests::class,
            QuestsImport::class,
        ],
        'Locations Give Items' => LocationsImport::class,
        'Locations' => LocationsImport::class,
        'Location Templates' => LocationTemplatesImport::class,
        'Npcs' => NpcsImport::class,
    ];

    /**
     * Import the files based on the directory.
     */
    public function importFile(string $dirName, string $path, int $index): void
    {
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
