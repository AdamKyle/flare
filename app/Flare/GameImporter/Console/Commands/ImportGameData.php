<?php

namespace App\Flare\GameImporter\Console\Commands;

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
    protected $signature = 'import:game-data';

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

        $this->line('Importing non map speficic data ...');

        $this->import($excelMapper, $files['Core Imports'], 'Core Imports');
        $this->import($excelMapper, $files['Skills'], 'Skills');
        $this->import($excelMapper, $files['Items'], 'Items');
        $this->import($excelMapper, $files['Affixes'], 'Affixes');
        $this->import($excelMapper, $files['Kingdoms'], 'Kingdoms');

        $this->line('Importing maps ...');
        
        // // Import maps:
        $this->importGameMaps();

        $this->line('Importing map spefic data ...');

        // // This stuff depends on maps existing.
        $this->import($excelMapper, $files['.'], '.');

        // Update the game maps with specific modifiers and restrictions
        // based on the locations, imported above.
        $gameMaps = GameMap::all();

        foreach($gameMaps as $map) {
            $mapValue = new MapNameValue($map->name);

            $map->update($mapValue->getMapModifers());
        }

        $this->import($excelMapper, $files['Monsters'], 'Monsters');
        $this->import($excelMapper, $files['Admin Section'], 'Admin Section');

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

        $files = array_reverse($files);

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