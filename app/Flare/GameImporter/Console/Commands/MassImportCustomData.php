<?php

namespace App\Flare\GameImporter\Console\Commands;

use App\Flare\Models\GameMap;
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
     * Run the 2.0 release import stages in order, stopping at the first stage that fails.
     *
     * @return int
     *
     * @throws Exception
     */
    public function handle(): int
    {
        $exitCode = $this->runStages([
            ['Import World Gems', 'import:game-data', ['dirName' => 'World Gems']],
            ['Import Location Templates', 'import:game-data', ['dirName' => 'Location Templates']],
            ['Import Quests', 'import:game-data', ['dirName' => 'Quests']],
            ['Import Monsters', 'import:game-data', ['dirName' => 'Monsters']],
            ['Remove racial stat bonuses', 'remove:racial-stat-bonuses', []],
        ]);

        if ($exitCode !== self::SUCCESS) {
            return $exitCode;
        }

        if (config('app.env') !== 'production') {
            $this->importBaseGameMaps();
        }

        return $this->runStages([
            ['Restore base Game Map pieces', 'break:maps-into-pieces', []],
            ['Synchronize Gem Worlds', 'create:gem-worlds', []],
            ['Remove all character Gems and compensate players', 'gem-rebuild:remove-alll-gems', []],
            ['Create Character attack data', 'create:character-attack-data', []],
            ['Generate Monster cache', 'generate:monster-cache', []],
            ['Create Quest cache', 'create:quest-cache', []],
        ]);
    }

    /**
     * Run each label, command, and parameters stage in order, returning the first failing exit code or success.
     *
     * @param array $stages
     * @return int
     */
    private function runStages(array $stages): int
    {
        foreach ($stages as [$label, $command, $parameters]) {
            $exitCode = $this->runStage($label, $command, $parameters);

            if ($exitCode !== self::SUCCESS) {
                return $exitCode;
            }
        }

        return self::SUCCESS;
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
     * Import the base Game Maps outside production, reporting the stage like an Artisan stage.
     *
     * @return void
     *
     * @throws Exception
     */
    private function importBaseGameMaps(): void
    {
        $this->line('START: Import base Game Maps');

        $this->importGameMaps();

        $this->info('COMPLETE: Import base Game Maps');
    }

    /**
     * Store each base Game Map image in progression order and create or update its Game Map record.
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

        usort($files, fn (string $firstFile, string $secondFile): int => array_search($firstFile, $correctOrder) - array_search($secondFile, $correctOrder));

        foreach ($files as $file) {
            $this->importGameMap($file);
        }
    }

    /**
     * Store one base Game Map image and create or update its Game Map record.
     *
     * @param string $file
     * @return void
     *
     * @throws Exception
     */
    private function importGameMap(string $file): void
    {
        $fileName = pathinfo($file, PATHINFO_FILENAME);

        $path = Storage::disk('maps')->putFile($fileName, new File(resource_path('maps').'/'.$file));

        $mapValue = MapName::from($fileName);

        $gameMap = GameMap::where('name', $fileName)->first();

        if (! is_null($gameMap)) {
            $gameMap->update([
                'path' => $path,
            ]);

            return;
        }

        GameMap::create(array_merge([
            'name' => $fileName,
            'path' => $path,
            'default' => $mapValue->isSurface(),
            'kingdom_color' => MapName::kingdomColors()[$fileName],
        ], $mapValue->getMapModifers()));
    }
}
