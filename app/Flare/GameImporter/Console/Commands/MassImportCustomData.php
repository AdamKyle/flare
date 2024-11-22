<?php

namespace App\Flare\GameImporter\Console\Commands;

use App\Flare\Models\GameMap;
use App\Flare\Models\InfoPage;
use App\Flare\Models\Item;
use App\Flare\Models\Survey;
use App\Flare\Values\MapNameValue;
use Artisan;
use Illuminate\Console\Command;
use Illuminate\Http\File;
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
     */
    public function handle()
    {

        Item::where('type', 'alchemy')->where('name', 'like', '%Oil')->where('holy_level', '>', 5)->delete();

        $gameMaps = GameMap::all();

        foreach ($gameMaps as $gameMap) {
            if ($gameMap->mapType()->isShadowPlane()) {
                $gameMap->update([
                    'xp_bonus' => 0.05,
                    'skill_training_bonus' => 0.05,
                    'drop_chance_bonus' => 0.08,
                    'enemy_stat_bonus' => 0.03,
                    'character_attack_reduction' => 0.05,
                ]);
            }

            if ($gameMap->mapType()->isHell()) {
                $gameMap->update([
                    'xp_bonus' => 0.1,
                    'skill_training_bonus' => 0.1,
                    'drop_chance_bonus' => 0.1,
                    'enemy_stat_bonus' => 0.05,
                    'character_attack_reduction' => 0.08,
                ]);
            }

            if ($gameMap->mapType()->isPurgatory()) {
                $gameMap->update([
                    'xp_bonus' => 0.15,
                    'skill_training_bonus' => 0.15,
                    'drop_chance_bonus' => 0.12,
                    'enemy_stat_bonus' => 0.12,
                    'character_attack_reduction' => 0.10,
                ]);
            }

            if (
                $gameMap->mapType()->isTheIcePlane() ||
                $gameMap->mapType()->isDelusionalMemories()
            ) {
                $gameMap->update([
                    'xp_bonus' => 0.25,
                    'skill_training_bonus' => 0.20,
                    'drop_chance_bonus' => 0.20,
                    'enemy_stat_bonus' => 0.06,
                    'character_attack_reduction' => 0.03,
                ]);
            }

            if ($gameMap->mapType()->isTwistedMemories()) {
                $gameMap->update([
                    'xp_bonus' => 0.20,
                    'skill_training_bonus' => 0.20,
                    'drop_chance_bonus' => 0.15,
                    'enemy_stat_bonus' => 0.15,
                    'character_attack_reduction' => 0.13,
                ]);
            }
        }

        Artisan::call('import:game-data Items');
        Artisan::call('import:game-data Quests');
        Artisan::call('balance:monsters');
        Artisan::call('generate:monster-cache');
        Artisan::call('create:character-attack-data');

        $this->importInformationSection();

        if (config('app.env') !== 'production') {
            $this->importGameMaps();
        }

        $this->importSurveys();
    }

    /**
     * Import the information sectionbalance:monsters
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

        $deleteCommand = 'rm -rf ' . escapeshellarg($destinationDirectory) . './info-sections-images';
        exec($deleteCommand, $output, $exitCode);

        if ($exitCode !== 0) {
            $this->error('Could not delete the info-section-images directory');

            return;
        }

        $command = 'cp -R ' . escapeshellarg($sourceDirectory) . ' ' . escapeshellarg($destinationDirectory);
        exec($command, $output, $exitCode);

        if ($exitCode === 0) {
            $this->line('Information section images directory copied to public successfully. Information section is now set up.');
        } else {
            $this->error('Failed to copy the information images directory over. You can do this manually from the resources/backup/information-sections-images. Copy the entire directory to app/public');
        }
    }

    /**
     * Import surveys.
     *
     * @return void
     */
    private function importSurveys(): void
    {
        $data = Storage::disk('data-imports')->get('Admin Section/surveys.json');

        $data = json_decode(trim($data), true);

        foreach ($data as $modelEntry) {
            Survey::updateOrCreate(['id' => $modelEntry['id']], $modelEntry);
        }

        $this->line('Surveys have been imported!');
    }

    /**
     * Import the game maps
     *
     * @return void
     */
    private function importGameMaps(): void
    {
        $files = Storage::disk('data-maps')->allFiles();

        $corectOrder = [
            'Surface.png',
            'Labyrinth.png',
            'Dungeons.png',
            'Shadow Plane.png',
            'Hell.png',
            'Purgatory.png',
            'IcePlane.png',
            'Twisted Memories.png',
            'Delusional Memories.png',
        ];

        // Sort the array such that the maps are in the correct order.
        usort($files, function ($a, $b) use ($corectOrder) {
            $indexA = array_search($a, $corectOrder);
            $indexB = array_search($b, $corectOrder);

            return $indexA - $indexB;
        });

        foreach ($files as $file) {
            $fileName = pathinfo($file, PATHINFO_FILENAME);

            $path = Storage::disk('maps')->putFile($fileName, new File(resource_path('maps') . '/' . $file));

            $mapValue = new MapNameValue($fileName);

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
                'kingdom_color' => MapNameValue::$kingdomColors[$fileName],
            ], (new MapNameValue($fileName))->getMapModifers());

            GameMap::create($gameMapData);
        }
    }
}
