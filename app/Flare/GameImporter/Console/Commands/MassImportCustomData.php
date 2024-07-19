<?php

namespace App\Flare\GameImporter\Console\Commands;

use App\Flare\Models\Event;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\FactionLoyaltyNpcTask;
use App\Flare\Models\GameMap;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Models\InfoPage;
use App\Flare\Values\MapNameValue;
use App\Game\Events\Values\EventType;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class MassImportCustomData extends Command {

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
    public function handle() {

        $this->importGameMaps();

        $factionLoyaltyIds = FactionLoyalty::first()->whereDoesntHave('faction')->whereDoesntHave('character')->pluck('id');
        $factionLoyaltyNpcIds = FactionLoyaltyNpc::whereIn('faction_loyalty_id', $factionLoyaltyIds->toArray())->pluck('id');

        FactionLoyaltyNpcTask::whereIn('faction_loyalty_npc_id', $factionLoyaltyNpcIds)->delete();
        FactionLoyaltyNpc::whereIn('faction_loyalty_id', $factionLoyaltyIds->toArray())->delete();
        FactionLoyalty::first()->whereDoesntHave('faction')->whereDoesntHave('character')->delete();

        Artisan::call('delete:flagged-users');
        Artisan::call('import:game-data "Items"');
        Artisan::call('import:game-data "Affixes"');
        Artisan::call('import:game-data "Locations"');
        Artisan::call('import:game-data "Monsters"');
        Artisan::call('import:game-data "Npcs"');
        Artisan::call('import:game-data "Kingdoms"');
        Artisan::call('import:game-data "Kingdom Passive Skills"');
        Artisan::call('import:game-data "Quests"');

        Artisan::call('assign:new-skills');
        Artisan::call('assign:new-npcs-to-faction-loyalty');
        Artisan::call('assign:new-buildings-to-existing-kingdoms');
        Artisan::call('adjust:affixes-attached-to-items');
        Artisan::call('balance:monsters');
        Artisan::call('create:character-attack-data');
        Artisan::call('generate:monster-cache');
        Artisan::call('create:quest-cache');
        Artisan::call('end:scheduled-event');

        $this->importInformationSection();
    }

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

    protected function importGameMaps(): void {
        $files = Storage::disk('data-maps')->allFiles();

        $corectOrder = [
            "Surface.png",
            "Labyrinth.png",
            "Dungeons.png",
            "Shadow Plane.png",
            "Hell.png",
            "Purgatory.png",
            "IcePlane.png",
            "Twisted Memories.png",
            "Delusional Memories.png",
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

            $gameMap = GameMap::where('name', $fileName)->first();

            if (!is_null($gameMap)) {
                $gameMap->update([
                    'path' => $path,
                ]);

                continue;
            }

            $gameMapData = array_merge([
                'name'          => $fileName,
                'path'          => $path,
                'default'       => $mapValue->isSurface(),
                'kingdom_color' => MapNameValue::$kingdomColors[$fileName],
            ], (new MapNameValue($fileName))->getMapModifers());

            GameMap::create($gameMapData);
        }
    }
}
