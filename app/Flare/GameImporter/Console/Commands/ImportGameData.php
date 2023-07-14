<?php

namespace App\Flare\GameImporter\Console\Commands;

use App\Flare\Models\InfoPage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Flare\GameImporter\Values\ExcelMapper;
use App\Flare\Values\MapNameValue;

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

        dd($this->importGameMaps());

        $files    = $this->fetchFiles();

        $this->import($excelMapper, $files['Core Imports'], 'Core Imports');
        $this->import($excelMapper, $files['Skills'], 'Skills');
        $this->import($excelMapper, $files['Items'], 'Items');
        $this->import($excelMapper, $files['Affixes'], 'Affixes');
        $this->import($excelMapper, $files['Monsters'], 'Monsters');
        $this->import($excelMapper, $files['Kingdoms'], 'Kingdoms');
        $this->import($excelMapper, $files['.'], '.');
        $this->import($excelMapper, $files['Admin Section'], 'Admin Section');

        // Import information section:
        $this->importInformationSection();

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

        array_reverse($result['Admin Section']);

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
        $data = Storage::disk('data-imports')->get('/Admin Section/information.json');

        foreach ($data as $key => $modelEntry) {
            InfoPage::updateOrCreate(['id' => $modelEntry['id']], $modelEntry);
        }
    }

    /**
     * Import the game maps.
     *
     * @return void
     */
    protected function importGameMaps(): void {
        $files = File::files(resource_path('maps'));

        foreach ($files as $file) {
            $fileName = pathinfo($file->getFilename(), PATHINFO_FILENAME);

            $path = Storage::disk('maps')->putFile($fileName, $file);

            $gameMapData = [
                'name'          => $fileName,
                'path'          => $path,
                'default'       => (new MapNameValue($fileName))->isSurface(),
                'kingdom_color' => MapNameValue::$kingdomColors[$fileName],
            ];
        }
    }
}
