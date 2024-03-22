<?php

namespace App\Flare\GameImporter\Console\Commands;

use Illuminate\Support\Str;
use App\Flare\Models\GameMap;
use App\Flare\Models\InfoPage;
use Illuminate\Console\Command;
use App\Flare\Values\MapNameValue;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use App\Flare\GameImporter\Values\ExcelMapper;

class ImportGameData extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:game-data {dirName?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports Game Data';

    /**
     * Execute the console command.
     */
    public function handle(ExcelMapper $excelMapper) {

        ini_set('memory_limit', '-1');

        $this->line('Fetching files ...');

        $files = $this->fetchFiles();

        $dirNameForReImport = $this->argument('dirName');

        if (!is_null($dirNameForReImport)) {

            $dirNameForReImport = Str::title($dirNameForReImport);

            if (!isset($files[$dirNameForReImport])) {

                return $this->error('No directory in data-imports for: ' . $dirNameForReImport);
            }

            $this->line('Re importing: ' . $dirNameForReImport);

            $this->import($excelMapper, $files[$dirNameForReImport], $dirNameForReImport);

            return $this->line('All done ...');
        }

        $this->line('Importing maps ...');

        // // Import maps:
        $this->importGameMaps();

        $this->import($excelMapper, $files['Locations Give Items'], 'Locations Give Items');

        $this->line('Importing non map speficic data ...');

        $this->import($excelMapper, $files['Core Imports'], 'Core Imports');
        $this->import($excelMapper, $files['Skills'], 'Skills');
        $this->import($excelMapper, $files['Items'], 'Items');
        $this->import($excelMapper, $files['Affixes'], 'Affixes');
        $this->import($excelMapper, $files['Kingdoms'], 'Kingdoms');
        $this->import($excelMapper, $files['Kingdom Passive Skills'], 'Kingdom Passive Skills');

        $this->line('Importing map spefic data ...');

        $this->import($excelMapper, $files['Locations'], 'Locations');
        $this->import($excelMapper, $files['Npcs'], 'Npcs');

        // // This stuff depends on maps existing.
        $this->import($excelMapper, $files['.'], '.');

        // Update the game maps with specific modifiers and restrictions
        // based on the locations, imported above.
        $gameMaps = GameMap::all();

        foreach ($gameMaps as $map) {
            $mapValue = new MapNameValue($map->name);

            $map->update($mapValue->getMapModifers());
        }

        // Finish Excel Imports
        $this->import($excelMapper, $files['Monsters'], 'Monsters');
        $this->import($excelMapper, $files['Admin Section'], 'Admin Section');
        $this->import($excelMapper, $files['Raids'], 'Raids');
        $this->import($excelMapper, $files['Quests'], 'Quests');

        // Due to the way quests are ordered, and their dependencies on other quests we have to double import to make
        // sure all relationships are properly setup.
        $this->import($excelMapper, $files['Quests'], 'Quests');

        $this->line('Importing Infromation section ...');

        // Import the information wiki
        $this->importInformationSection();

        $this->line('Finished the import ...');
    }

    /**
     * Fetch the files.
     *
     * The mapper used to import these files expect the file list to be in a specific
     * order, in some instances, so we sort and make sure the admin section is reversed.
     *
     * @return array
     */
    protected function fetchFiles(): array {
        $files   = Storage::disk('data-imports')->allFiles();

        $result  = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'xlsx') {
                $path = pathinfo($file, PATHINFO_DIRNAME);

                if (!isset($result[$path])) {
                    $result[$path] = [];
                }

                $result[$path][] = $file;

                sort($result[$path]);
            }
        }

        $result['Kingdoms'] = array_reverse($result['Kingdoms']);

        return $result;
    }

    /**
     * Import th excel files.
     *
     * @param ExcelMapper $excelMapper
     * @param array $files
     * @param string $directoryName
     * @return void
     */
    protected function import(ExcelMapper $excelMapper, array $files, string $directoryName): void {
        foreach ($files as $index => $path) {
            $path = resource_path('data-imports') . '/' . $path;

            $excelMapper->importFile($directoryName, $path, $index);
        }
    }

    /**
     * Import the information section.
     *
     * @return void
     */
    protected function importInformationSection(): void {
        $data = Storage::disk('data-imports')->get('Admin Section/information.json');

        $data = json_decode(trim($data), true);

        foreach ($data as $modelEntry) {
            InfoPage::updateOrCreate(['id' => $modelEntry['id']], $modelEntry);
        }

        $sourceDirectory      = resource_path('backup/info-sections-images');
        $destinationDirectory = storage_path('app/public');

        $command = 'cp -R ' . escapeshellarg($sourceDirectory) . ' ' . escapeshellarg($destinationDirectory);
        exec($command, $output, $exitCode);

        if ($exitCode === 0) {
            $this->line('Information section images directory copied to public successfully. Information section is now set up.');
        } else {
            $this->line('Failed to copy the information images directory over. You can do this manually from the resources/backup/information-sections-images. Copy the entire directory to app/public');
        }
    }

    /**
     * Import the game maps.
     *
     * @return void
     */
    protected function importGameMaps(): void {
        $files = Storage::disk('data-maps')->allFiles();

        $corectOrder = [
            "Surface.jpg",
            "Labyrinth.jpeg",
            "Dungeons.jpeg",
            "Shadow Plane.jpeg",
            "Hell.jpeg",
            "Purgatory.jpeg",
            "IcePlane.jpeg",
            "Twisted Memories.jpeg",
        ];

        // Sort the array such that the maps are in the correct order.
        usort($files, function ($a, $b) use ($corectOrder) {
            $indexA = array_search($a, $corectOrder);
            $indexB = array_search($b, $corectOrder);

            return $indexA - $indexB;
        });

        foreach ($files as $file) {
            $fileName = pathinfo($file, PATHINFO_FILENAME);

            $path     = Storage::disk('maps')->putFile($fileName, new File(resource_path('maps') . '/' . $file));

            $mapValue = new MapNameValue($fileName);

            $gameMapData = [
                'name'          => $fileName,
                'path'          => $path,
                'default'       => $mapValue->isSurface(),
                'kingdom_color' => MapNameValue::$kingdomColors[$fileName],
            ];

            GameMap::create($gameMapData);
        }
    }
}
