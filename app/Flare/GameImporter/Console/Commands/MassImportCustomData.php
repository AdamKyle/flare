<?php

namespace App\Flare\GameImporter\Console\Commands;

use App\Flare\Models\GameMap;
use App\Flare\Models\InfoPage;
use App\Game\Maps\Values\MapName;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class MassImportCustomData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mass:import-game-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports Game Data in a specific way defined by the programmer';

    /**
     * Execute the console command.
     *
     * @return void
     *
     * @throws Exception
     */
    public function handle()
    {
        if ($this->runStage('Import World Gems', 'import:game-data', ['dirName' => 'World Gems']) !== self::SUCCESS) {
            return;
        }

        if ($this->runStage('Import Location Templates', 'import:game-data', ['dirName' => 'Location Templates']) !== self::SUCCESS) {
            return;
        }

        if ($this->runStage('Import Quests', 'import:game-data', ['dirName' => 'Quests']) !== self::SUCCESS) {
            return;
        }

        if ($this->runStage('Remove racial stat bonuses', 'remove:racial-stat-bonuses') !== self::SUCCESS) {
            return;
        }

        if (config('app.env') !== 'production' && ! $this->importBaseGameMaps()) {
            return;
        }

        if ($this->runStage('Restore base Game Map pieces', 'break:maps-into-pieces') !== self::SUCCESS) {
            return;
        }

        if ($this->runStage('Synchronize Gem Worlds', 'create:gem-worlds') !== self::SUCCESS) {
            return;
        }

        if ($this->runStage('Create Character attack data', 'create:character-attack-data') !== self::SUCCESS) {
            return;
        }

        if ($this->runStage('Generate Monster cache', 'generate:monster-cache') !== self::SUCCESS) {
            return;
        }

        $this->runStage('Create Quest cache', 'create:quest-cache');
    }

    /**
     * Run a single Artisan bootstrap stage, surfacing its output and reporting its outcome.
     *
     * @param string $label
     * @param string $command
     * @param array $parameters
     * @return int
     */
    private function runStage(string $label, string $command, array $parameters = []): int
    {
        $this->line('START: '.$label);

        $exitCode = Artisan::call($command, $parameters);

        $output = trim(Artisan::output());

        if ($output !== '') {
            $this->line($output);
        }

        if ($exitCode === self::SUCCESS) {
            $this->info('COMPLETE: '.$label);

            return $exitCode;
        }

        $this->error('FAILED: '.$label.' (exit code '.$exitCode.')');

        return $exitCode;
    }

    /**
     * Import the information section
     *
     * @return void
     */
    private function importInformationSection(): void
    {

        InfoPage::truncate();

        $data = Storage::disk('data-imports')->get('Admin Section/information.json');

        $data = json_decode(trim($data), true);

        foreach ($data as $modelEntry) {
            InfoPage::updateOrCreate(['id' => $modelEntry['id']], $modelEntry);
        }

        $sourceDirectory = resource_path('backup/info-sections-images');
        $destinationDirectory = storage_path('app/public');

        $deleteCommand = 'rm -rf '.escapeshellarg($destinationDirectory).'./info-sections-images';
        exec($deleteCommand, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->error('Could not delete the info-section-images directory');

            return;
        }

        $command = 'cp -R '.escapeshellarg($sourceDirectory).' '.escapeshellarg($destinationDirectory);
        exec($command, $output, $exitCode);

        if ($exitCode === 0) {
            $this->line('Information section images directory copied to public successfully. Information section is now set up.');
        } else {
            $this->error('Failed to copy the information images directory over. You can do this manually from the resources/backup/information-sections-images. Copy the entire directory to app/public');
        }
    }

    /**
     * Import the base Game Maps outside production, reporting the stage outcome like an Artisan stage.
     *
     * @return bool
     *
     * @throws Exception
     */
    private function importBaseGameMaps(): bool
    {
        $this->line('START: Import base Game Maps');

        $this->importGameMaps();

        $this->info('COMPLETE: Import base Game Maps');

        return true;
    }

    /**
     * Import the game maps
     *
     * @return void
     *
     * @throws Exception
     */
    private function importGameMaps(): void
    {
        $files = Storage::disk('data-maps')->allFiles();

        $correctOrder = [
            'Surface.png',
            'Labyrinth.png',
            'Dungeons.png',
            'Shadow Plane.png',
            'Hell.png',
            'Purgatory.png',
            'The Ice Plane.png',
            'Twisted Memories.png',
            'Delusional Memories.png',
        ];

        // Sort the array such that the maps are in the correct order.
        usort($files, function ($a, $b) use ($correctOrder) {
            $indexA = array_search($a, $correctOrder);
            $indexB = array_search($b, $correctOrder);

            return $indexA - $indexB;
        });

        foreach ($files as $file) {
            $fileName = pathinfo($file, PATHINFO_FILENAME);

            $path = Storage::disk('maps')->putFile($fileName, new File(resource_path('maps').'/'.$file));

            $mapValue = MapName::from($fileName);

            $gameMap = GameMap::where('name', $fileName)->first();

            if (! is_null($gameMap)) {
                $gameMap->update([
                    'path' => $path,
                ]);

                continue;
            }

            $gameMapData = array_merge([
                'name' => $fileName,
                'path' => $path,
                'default' => $mapValue->isSurface(),
                'kingdom_color' => MapName::kingdomColors()[$fileName],
            ], (MapName::from($fileName))->getMapModifers());

            GameMap::create($gameMapData);
        }
    }
}
